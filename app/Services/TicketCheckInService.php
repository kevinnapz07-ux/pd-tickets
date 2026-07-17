<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TicketCheckInService
{
    public function __construct(private TicketStatusService $statuses) {}

    public function checkIn(Registration $registration, User $admin): Registration
    {
        return DB::transaction(function () use ($registration, $admin): Registration {
            $locked = Registration::query()->with(['event', 'payment', 'checkedInBy'])->lockForUpdate()->findOrFail($registration->getKey());
            $status = $this->statuses->status($locked);

            if ($status === 'used') {
                throw new RuntimeException('Tiket sudah digunakan pada '.$locked->checked_in_at?->format('d M Y H:i').'.');
            }

            if ($status !== 'valid') {
                Log::warning('Ticket check-in rejected.', ['admin_id' => $admin->id, 'registration_id' => $locked->id, 'ticket_status' => $status]);
                $message = match ($status) {
                    'cancelled' => 'Tiket telah dibatalkan.',
                    'payment_failed' => 'Tiket belum aktif karena pembayaran gagal.',
                    'payment_expired' => 'Tiket belum aktif karena pembayaran kedaluwarsa.',
                    'payment_cancelled' => 'Tiket belum aktif karena pembayaran dibatalkan.',
                    'refunded' => 'Tiket tidak dapat digunakan karena pembayaran telah dikembalikan.',
                    default => 'Tiket belum aktif karena pembayaran atau registrasi belum valid.',
                };

                throw new RuntimeException($message);
            }

            $locked->update([
                'registration_status' => 'checked_in',
                'checked_in_at' => now(),
                'checked_in_by' => $admin->id,
            ]);

            Log::notice('Ticket checked in.', ['admin_id' => $admin->id, 'registration_id' => $locked->id, 'ticket_code' => $locked->registration_code]);

            return $locked->fresh(['event', 'payment', 'checkedInBy']);
        }, 3);
    }
}
