<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Services\MidtransSnapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RegistrationController extends Controller
{
    public function store(Request $request, Event $event, MidtransSnapService $midtrans): RedirectResponse
    {
        abort_unless($event->is_published, 404);
        abort_unless($request->user()?->role === 'peserta', 403);

        if (! $event->registration_is_open) {
            return back()->withErrors(['event' => 'Pendaftaran event ini belum dibuka.']);
        }

        if ($event->available_seats < 1) {
            return back()->withInput()->withErrors(['event' => 'Kuota event sudah penuh.']);
        }

        $categories = collect($event->registrationCategories());
        $categoryKeys = $categories->pluck('key')->all();
        $selectedCategory = $categories->firstWhere('key', $request->input('participant_type')) ?? $categories->first();
        $selectedFields = collect($selectedCategory['fields'] ?? []);
        $registrationColumns = ['name', 'email', 'phone', 'student_id', 'campus_area', 'class_year', 'study_program', 'faculty', 'notes'];

        $rules = [
            'participant_type' => ['required', Rule::in($categoryKeys)],
        ];

        foreach ($selectedFields as $field) {
            $rules[$field] = match ($field) {
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:120', Rule::unique('registrations')->where('event_id', $event->id)],
                'phone' => ['required', 'string', 'max:30'],
                'gender' => ['required', Rule::in(['laki_laki', 'perempuan'])],
                'domicile' => ['required', 'string', 'max:120'],
                'student_id' => ['required', 'string', 'max:30'],
                'campus_area' => ['required', Rule::in(['depok', 'kalimalang', 'karawaci', 'cengkareng', 'salemba'])],
                'class_year' => ['required', 'string', 'max:10'],
                'study_program' => ['required', 'string', 'max:80'],
                'faculty' => ['nullable', 'string', 'max:80'],
                'notes' => ['nullable', 'string', 'max:500'],
                default => ['required', 'string', 'max:255'],
            };
        }

        $data = $request->validate($rules, [
            'email.unique' => 'Email ini sudah terdaftar untuk event tersebut.',
        ]);

        $customFields = [];

        foreach ($selectedFields->diff($registrationColumns) as $field) {
            $customFields[$field] = $data[$field] ?? null;
            unset($data[$field]);
        }

        $data = [
            'name' => $data['name'] ?? $request->user()->name,
            'email' => $data['email'] ?? $request->user()->email,
            'phone' => $data['phone'] ?? '-',
            'participant_type' => $data['participant_type'],
            'student_id' => $data['student_id'] ?? null,
            'campus_area' => $data['campus_area'] ?? null,
            'class_year' => $data['class_year'] ?? null,
            'study_program' => $data['study_program'] ?? null,
            'faculty' => $data['faculty'] ?? null,
            'notes' => $data['notes'] ?? null,
            'custom_fields' => array_filter($customFields, fn ($value): bool => filled($value)),
        ];

        if (Registration::where('event_id', $event->id)->where('email', $data['email'])->exists()) {
            return back()->withInput()->withErrors(['email' => 'Email ini sudah terdaftar untuk event tersebut.']);
        }

        $registration = Registration::create([
            ...$data,
            'event_id' => $event->id,
            'user_id' => $request->user()->id,
            'registration_code' => 'PDG-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'payment_status' => $event->price > 0 ? 'pending' : 'paid',
            'registration_status' => $event->price > 0 ? null : 'registered',
        ]);

        if ($event->price <= 0) {
            return redirect()->route('registrations.show', $registration)
                ->with('status', 'Registrasi berhasil. Event ini gratis.');
        }

        $payment = Payment::create([
            'registration_id' => $registration->id,
            'order_id' => 'PDG-'.$registration->id.'-'.now()->timestamp,
            'amount' => $event->price,
        ]);

        try {
            $snap = $midtrans->createTransaction($payment);
        } catch (RuntimeException $exception) {
            return redirect()->route('registrations.show', $registration)
                ->with('payment_error', $exception->getMessage());
        }

        $payment->update([
            'snap_token' => $snap['token'] ?? null,
            'redirect_url' => $snap['redirect_url'] ?? null,
            'payload' => $snap,
        ]);

        return redirect()->route('registrations.show', $registration);
    }

    public function show(Request $request, Registration $registration): View
    {
        $user = $request->user();

        abort_unless(
            $user->role === 'admin'
            || $registration->user_id === $user->id
            || ($registration->user_id === null && $registration->email === $user->email),
            403
        );

        $registration->load(['event', 'payment']);

        return view('registrations.show', compact('registration'));
    }

    public function refreshPaymentStatus(
        Request $request,
        Registration $registration,
        MidtransSnapService $midtrans
    ): JsonResponse|RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user->role === 'admin'
            || $registration->user_id === $user->id
            || ($registration->user_id === null && $registration->email === $user->email),
            403
        );

        $payment = $registration->payment;

        if (! $payment) {
            $message = 'Data pembayaran tidak ditemukan.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 404)
                : back()->withErrors(['payment' => $message]);
        }

        try {
            $status = $midtrans->syncPaymentStatus($payment);
        } catch (RuntimeException $exception) {
            return $request->expectsJson()
                ? response()->json(['message' => $exception->getMessage()], 422)
                : back()->withErrors(['payment' => $exception->getMessage()]);
        }

        $message = 'Status pembayaran berhasil diverifikasi langsung ke Midtrans: '.Registration::transactionStatusLabels()[$status].'.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'status' => $status])
            : back()->with('status', $message);
    }

    public function initializePayment(
        Request $request,
        Registration $registration,
        MidtransSnapService $midtrans
    ): JsonResponse|RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user->role === 'admin'
            || $registration->user_id === $user->id
            || ($registration->user_id === null && $registration->email === $user->email),
            403
        );

        try {
            $registration->load(['event', 'payment']);
            $payment = $registration->payment;

            if (! $user->canUseParticipantFeatures()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Akun belum dapat menggunakan fitur pembayaran.'], 403);
                }

                return back()->withErrors(['payment' => 'Akun belum dapat menggunakan fitur pembayaran.']);
            }

            if (! $payment || ! $registration->event || $registration->event->price <= 0) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Data pembayaran tidak ditemukan.'], 404);
                }

                return back()->withErrors(['payment' => 'Data pembayaran tidak ditemukan.']);
            }

            if ($registration->payment_status !== 'pending') {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Pembayaran ini tidak lagi menunggu pembayaran.'], 409);
                }

                return back()->with('status', 'Pembayaran ini tidak lagi menunggu pembayaran.');
            }

            if ($payment->snap_token) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Pembayaran sudah siap dilanjutkan.',
                        'snap_token' => $payment->snap_token,
                        'redirect_url' => $payment->redirect_url,
                    ]);
                }

                return back()->with('status', 'Pembayaran sudah siap dilanjutkan.');
            }

            // A fresh order ID avoids reusing an uncertain transaction after a connection failure.
            $payment->update([
                'order_id' => 'PDG-'.$registration->id.'-'.now()->timestamp.'-'.Str::upper(Str::random(4)),
            ]);

            $snap = $midtrans->createTransaction($payment->fresh());

            $payment->update([
                'snap_token' => $snap['token'] ?? null,
                'redirect_url' => $snap['redirect_url'] ?? null,
                'payload' => $snap,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Pembayaran berhasil disiapkan.',
                    'snap_token' => $payment->snap_token,
                    'redirect_url' => $payment->redirect_url,
                ]);
            }

            return back()->with('status', 'Pembayaran berhasil disiapkan. Silakan lanjutkan pembayaran.');
        } catch (RuntimeException $exception) {
            return $request->expectsJson()
                ? response()->json(['message' => $exception->getMessage()], 422)
                : back()->with('payment_error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            $message = 'Pembayaran belum dapat disiapkan karena terjadi gangguan pada server. Silakan coba kembali atau hubungi admin.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 503)
                : back()->with('payment_error', $message);
        }
    }
}
