<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ParticipantProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('profile.participant', compact('user'));
    }

    public function activity(Request $request): View
    {
        $user = $request->user();

        $registrations = Registration::with(['event', 'payment'])
            ->whereHas('event', fn ($query) => $query
                ->where(fn ($eventQuery) => $eventQuery
                    ->where('ends_at', '>', now())
                    ->orWhere(fn ($withoutEndTime) => $withoutEndTime
                        ->whereNull('ends_at')
                        ->where('starts_at', '>=', now())
                    )
                )
            )
            ->where(fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhere(fn ($legacyQuery) => $legacyQuery
                    ->whereNull('user_id')
                    ->where('email', $user->email)
                )
            )
            ->latest()
            ->get();

        return view('profile.activity', compact('registrations'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::min(8), 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('participant.profile')
            ->with('status', 'Password berhasil diperbarui.');
    }

    public function cancel(Request $request, Registration $registration): RedirectResponse
    {
        $registration->load('event');

        abort_unless(
            $registration->user_id === $request->user()->id
            || ($registration->user_id === null && $registration->email === $request->user()->email),
            403
        );

        if ($registration->event->price > 0) {
            return back()->withErrors(['registration' => 'Event berbayar tidak dapat dibatalkan dari profil. Silakan konfirmasi ke pengurus.']);
        }

        $eventTitle = $registration->event->title;
        $registration->update(['registration_status' => 'cancelled']);

        return redirect()->route('tickets.index')
            ->with('status', 'Registrasi event '.$eventTitle.' berhasil dibatalkan.');
    }
}
