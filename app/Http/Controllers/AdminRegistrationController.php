<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Services\TicketCheckInService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class AdminRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $registrations = Registration::with(['event', 'payment'])
            ->when($request->query('event_id'), fn ($query, $eventId) => $query->where('event_id', $eventId))
            ->when($request->query('transaction_status'), fn ($query, $status) => $query->where('payment_status', $status))
            ->when($request->query('registration_status'), fn ($query, $status) => $query->where('registration_status', $status))
            ->when($request->query('search'), function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('registration_code', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $events = Event::orderBy('title')->get();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'events' => $events,
            'transactionStatuses' => Registration::transactionStatusLabels(),
            'registrationStatuses' => Registration::registrationStatusLabels(),
        ]);
    }

    public function updateStatus(Request $request, Registration $registration): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'registration_status' => ['nullable', Rule::in(array_keys(Registration::registrationStatusLabels()))],
        ]);

        $registration->update($data);

        return back()->with('status', 'Status pendaftaran peserta berhasil diperbarui. Status pembayaran hanya diperbarui oleh webhook Midtrans.');
    }

    public function showCheckIn(Request $request, string $registrationCode): View
    {
        $this->authorizeAdmin($request);

        $registration = Registration::with(['event', 'payment'])
            ->where('registration_code', $registrationCode)
            ->firstOrFail();

        return view('admin.registrations.check-in', compact('registration'));
    }

    public function checkIn(Request $request, Registration $registration, TicketCheckInService $checkIn): RedirectResponse
    {
        $this->authorizeAdmin($request);

        try {
            $checkIn->checkIn($registration, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['registration' => $exception->getMessage()]);
        }

        return back()->with('status', 'Check-in peserta berhasil dicatat.');
    }

    public function destroy(Request $request, Registration $registration): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $registration->delete();

        return back()->with('status', 'Data peserta berhasil dihapus.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
