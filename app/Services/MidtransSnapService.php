<?php

namespace App\Services;

use App\Models\Payment;
use App\Notifications\EventTicketNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransSnapService
{
    public function createTransaction(Payment $payment): array
    {
        $registration = $payment->registration()->with(['event', 'user'])->firstOrFail();
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur di file .env.');
        }

        $this->ensureEnvironmentMatchesKey($serverKey);

        if ($registration->user?->role !== 'peserta') {
            throw new RuntimeException('Akun belum dapat menggunakan fitur pembayaran.');
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $registration->user->name,
                'email' => $registration->user->email,
                'phone' => $registration->phone,
            ],
            'item_details' => [[
                'id' => 'EVENT-'.$registration->event_id,
                'price' => $payment->amount,
                'quantity' => 1,
                'name' => $registration->event->title,
            ]],
            'credit_card' => [
                'secure' => (bool) config('services.midtrans.is_3ds', true),
            ],
            'callbacks' => [
                'finish' => route('registrations.show', $registration),
            ],
        ];

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(3, 750)
                ->post($this->snapEndpoint(), $payload);
        } catch (ConnectionException $exception) {
            report($exception);

            throw new RuntimeException('Layanan pembayaran sedang tidak dapat dijangkau. Silakan coba kembali beberapa saat lagi.');
        } catch (RequestException $exception) {
    report($exception);

    \Illuminate\Support\Facades\Log::error(
        'Midtrans create transaction rejected',
        [
            'status' => $exception->response?->status(),
            'response_body' => $exception->response?->body(),
            'endpoint' => $this->snapEndpoint(),
            'merchant_id' => config('services.midtrans.merchant_id'),
            'is_production' => config(
                'services.midtrans.is_production'
            ),
            'server_key_prefix' => substr($serverKey, 0, 12),
            'server_key_length' => strlen($serverKey),
            'order_id' => $payment->order_id,
        ]
    );

    throw new RuntimeException(
        $this->transactionFailureMessage($exception)
    );
}

        if ($response->failed()) {
            throw new RuntimeException('Midtrans menolak transaksi: '.$response->body());
        }

        $responsePayload = $response->json();

        if (! is_array($responsePayload)) {
            Log::channel('stderr')->error('Midtrans Snap returned a non-JSON response.', [
                'status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'body_preview' => mb_substr(trim(strip_tags($response->body())), 0, 200),
                'production' => (bool) config('services.midtrans.is_production'),
                'order_id' => $payment->order_id,
            ]);

            throw new RuntimeException(
                'Respons layanan pembayaran tidak valid. Silakan hubungi admin untuk memeriksa koneksi Midtrans.'
            );
        }

        if (! filled($responsePayload['token'] ?? null)
            || ! filled($responsePayload['redirect_url'] ?? null)) {
            Log::channel('stderr')->error('Midtrans Snap response is missing required fields.', [
                'status' => $response->status(),
                'response_fields' => array_keys($responsePayload),
                'production' => (bool) config('services.midtrans.is_production'),
                'order_id' => $payment->order_id,
            ]);

            throw new RuntimeException(
                'Midtrans tidak mengembalikan token pembayaran. Silakan hubungi admin untuk memeriksa konfigurasi.'
            );
        }

        return $responsePayload;
    }

    public function hasValidSignature(array $payload): bool
{
    if (! isset(
        $payload['order_id'],
        $payload['status_code'],
        $payload['gross_amount'],
        $payload['signature_key']
    )) {
        return false;
    }

    $serverKey = trim((string) config('services.midtrans.server_key'));

    if ($serverKey === '') {
        return false;
    }

    $signature = hash(
        'sha512',
        (string) $payload['order_id']
        . (string) $payload['status_code']
        . (string) $payload['gross_amount']
        . $serverKey
    );

    return hash_equals(
        $signature,
        (string) $payload['signature_key']
    );
}

    public function syncPaymentStatus(Payment $payment): string
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur di file .env.');
        }

        $this->ensureEnvironmentMatchesKey($serverKey);

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(3, 750)
                ->get($this->statusEndpoint($payment->order_id));
        } catch (ConnectionException $exception) {
            report($exception);

            throw new RuntimeException('Status terbaru belum dapat diambil. Status akan diperbarui otomatis setelah Midtrans mengirimkan konfirmasi pembayaran.');
        } catch (RequestException $exception) {
            report($exception);

            throw new RuntimeException(match ($exception->response->status()) {
                401, 403 => 'Konfigurasi autentikasi Midtrans ditolak. Silakan hubungi admin.',
                default => 'Status pembayaran belum dapat diperiksa. Silakan coba kembali.',
            });
        }

        if ($response->failed()) {
            throw new RuntimeException('Status pembayaran belum dapat diperiksa. Silakan coba kembali.');
        }

        $payload = $response->json();

        if (($payload['order_id'] ?? null) !== $payment->order_id
            || ! $this->hasValidSignature($payload)
            || ! $this->amountMatches($payment, $payload['gross_amount'] ?? null)) {
            throw new RuntimeException('Respons status Midtrans tidak lolos validasi keamanan. Status pembayaran tidak diubah.');
        }

        return $this->processWebhook($payment, $payload);
    }

    public function amountMatches(Payment $payment, mixed $grossAmount): bool
    {
        if (! is_numeric($grossAmount)) {
            return false;
        }

        return (int) round(((float) $grossAmount) * 100) === $payment->amount * 100;
    }

    public function processWebhook(Payment $payment, array $payload): string
    {
        return DB::transaction(function () use ($payment, $payload): string {
            $lockedPayment = Payment::query()
                ->with(['registration.event', 'registration.user'])
                ->lockForUpdate()
                ->findOrFail($payment->id);
            $paymentStatus = $this->paymentStatusFromPayload($payload);
            $transactionStatus = (string) ($payload['transaction_status'] ?? 'pending');

            if ($lockedPayment->registration->payment_status === 'paid'
                && ! in_array($paymentStatus, ['paid', 'refunded'], true)) {
                $paymentStatus = 'paid';
                $transactionStatus = $lockedPayment->transaction_status;
            }

            if ($lockedPayment->registration->payment_status === 'refunded') {
                $paymentStatus = 'refunded';
                $transactionStatus = $lockedPayment->transaction_status;
            }

            $lockedPayment->update([
                'transaction_id' => $payload['transaction_id'] ?? $lockedPayment->transaction_id,
                'transaction_status' => $transactionStatus,
                'payment_type' => $payload['payment_type'] ?? $lockedPayment->payment_type,
                'paid_at' => $paymentStatus === 'paid' ? ($lockedPayment->paid_at ?? now()) : $lockedPayment->paid_at,
                'payload' => $payload,
            ]);

            $lockedPayment->registration->update([
                'payment_status' => $paymentStatus,
                'registration_status' => $paymentStatus === 'paid'
                    ? 'registered'
                    : $lockedPayment->registration->registration_status,
            ]);

            $account = $lockedPayment->registration->user;

            if ($paymentStatus === 'paid'
                && $lockedPayment->ticket_email_sent_at === null
                && $account?->role === 'peserta') {
                $account->notify(new EventTicketNotification($lockedPayment->registration));
                $lockedPayment->update(['ticket_email_sent_at' => now()]);
            }

            return $paymentStatus;
        }, 3);
    }

    private function paymentStatusFromPayload(array $payload): string
    {
        $transactionStatus = $payload['transaction_status'] ?? 'pending';
        $fraudStatus = $payload['fraud_status'] ?? null;

        return match (true) {
            $transactionStatus === 'settlement' => 'paid',
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => 'paid',
            $transactionStatus === 'expire' => 'expired',
            $transactionStatus === 'cancel' => 'cancelled',
            in_array($transactionStatus, ['refund', 'partial_refund'], true) => 'refunded',
            in_array($transactionStatus, ['deny', 'failure'], true) => 'failed',
            default => 'pending',
        };
    }

    private function snapEndpoint(): string
    {
        $baseUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';

        return $baseUrl.'/snap/v1/transactions';
    }

    private function statusEndpoint(string $orderId): string
    {
        $baseUrl = config('services.midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';

        return $baseUrl.'/v2/'.rawurlencode($orderId).'/status';
    }

    private function transactionFailureMessage(RequestException $exception): string
    {
        return match ($exception->response->status()) {
            401, 403 => 'Konfigurasi autentikasi Midtrans ditolak. Silakan hubungi admin.',
            400, 422 => 'Data pembayaran ditolak oleh Midtrans. Silakan hubungi admin untuk memeriksa konfigurasi transaksi.',
            default => 'Layanan pembayaran sedang mengalami gangguan. Silakan coba kembali beberapa saat lagi.',
        };
    }

    private function ensureEnvironmentMatchesKey(string $serverKey): void
    {
        if (trim($serverKey) === '') {
        throw new RuntimeException(
            'MIDTRANS_SERVER_KEY belum diatur.'
        );
        }
    }
}
