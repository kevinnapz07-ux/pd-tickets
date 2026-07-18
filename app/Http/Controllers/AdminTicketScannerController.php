<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\TicketCheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AdminTicketScannerController extends Controller
{
    public function index(): View
    {
        return view('admin.tickets.scanner');
    }

    public function checkIn(Request $request, TicketCheckInService $checkIn): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'ticket_reference' => ['required', 'string', 'max:500'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
        ]);
        $reference = trim($data['ticket_reference']);
        $token = str_contains($reference, '/ticket/verify/') ? basename(parse_url($reference, PHP_URL_PATH)) : $reference;
        $registration = Registration::query()
            ->with('event')
            ->where('verification_token', $token)
            ->orWhere('registration_code', $reference)
            ->first();

        if (! $registration) {
            return $this->failure($request, 'Tiket tidak ditemukan.', 404);
        }

        if (isset($data['event_id']) && $registration->event_id !== (int) $data['event_id']) {
            return $this->failure($request, 'Tiket berasal dari event yang berbeda.', 422);
        }

        try {
            $checkedIn = $checkIn->checkIn($registration, $request->user());
        } catch (RuntimeException $exception) {
            return $this->failure($request, $exception->getMessage(), 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Check-in berhasil.',
                'participant_name' => $checkedIn->name,
                'event_name' => $checkedIn->event->title,
                'ticket_code' => $checkedIn->registration_code,
                'checked_in_at' => $checkedIn->checked_in_at->translatedFormat('d M Y, H:i:s').' WIB',
            ]);
        }

        return back()->with('status', 'Check-in berhasil untuk '.$checkedIn->name.' ('.$checkedIn->registration_code.').');
    }

    private function failure(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], $status)
            : back()->withErrors(['ticket_reference' => $message]);
    }
}
