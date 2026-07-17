<?php

namespace App\Services;

use App\Models\Registration;

class TicketStatusService
{
    public function status(Registration $registration): string
    {
        if ($registration->registration_status === 'cancelled') {
            return 'cancelled';
        }

        if ($registration->checked_in_at || $registration->registration_status === 'checked_in') {
            return 'used';
        }

        $paymentStatus = match ($registration->payment_status) {
            'paid' => null,
            'failed' => 'payment_failed',
            'expired' => 'payment_expired',
            'cancelled' => 'payment_cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };

        if ($paymentStatus !== null) {
            return $paymentStatus;
        }

        return in_array($registration->registration_status, ['registered', 'completed'], true)
            ? 'valid'
            : 'pending';
    }
}
