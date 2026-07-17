<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\TicketCheckInService;
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

    public function checkIn(Request $request, TicketCheckInService $checkIn): RedirectResponse
    {
        $data = $request->validate(['ticket_reference' => ['required', 'string', 'max:500']]);
        $reference = trim($data['ticket_reference']);
        $token = str_contains($reference, '/ticket/verify/') ? basename(parse_url($reference, PHP_URL_PATH)) : $reference;
        $registration = Registration::query()
            ->where('verification_token', $token)
            ->orWhere('registration_code', $reference)
            ->first();

        if (! $registration) {
            return back()->withErrors(['ticket_reference' => 'Tiket tidak ditemukan atau QR tidak dikenali.']);
        }

        try {
            $checkedIn = $checkIn->checkIn($registration, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_reference' => $exception->getMessage()]);
        }

        return back()->with('status', 'Check-in berhasil untuk '.$checkedIn->name.' ('.$checkedIn->registration_code.').');
    }
}
