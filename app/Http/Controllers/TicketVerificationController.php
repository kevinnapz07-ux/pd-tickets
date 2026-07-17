<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\TicketStatusService;
use Illuminate\Http\Response;

class TicketVerificationController extends Controller
{
    public function __invoke(string $token, TicketStatusService $statuses): Response
    {
        $registration = Registration::with(['event', 'payment', 'checkedInBy'])->where('verification_token', $token)->first();

        if (! $registration) {
            return response()->view('tickets.verify', ['registration' => null, 'ticketStatus' => 'invalid'], 404);
        }

        return response()->view('tickets.verify', ['registration' => $registration, 'ticketStatus' => $statuses->status($registration)]);
    }
}
