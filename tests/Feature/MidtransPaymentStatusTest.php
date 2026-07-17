<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\MidtransSnapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Mockery\MockInterface;
use Tests\TestCase;

class MidtransPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_refresh_a_midtrans_payment_status(): void
    {
        Notification::fake();
        config([
            'services.midtrans.server_key' => 'sandbox-server-key',
            'services.midtrans.is_production' => false,
        ]);

        $statusPayload = [
                'order_id' => 'PDG-STATUS-001',
                'transaction_id' => 'midtrans-transaction-001',
                'transaction_status' => 'settlement',
                'payment_type' => 'qris',
                'status_code' => '200',
                'gross_amount' => '75000.00',
        ];
        $statusPayload['signature_key'] = hash(
            'sha512',
            $statusPayload['order_id'].$statusPayload['status_code'].$statusPayload['gross_amount'].config('services.midtrans.server_key')
        );

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/*/status' => Http::response($statusPayload),
        ]);

        $owner = User::factory()->create(['role' => 'peserta']);
        $event = Event::create([
            'title' => 'Event Midtrans',
            'description' => 'Event untuk pengujian status pembayaran.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 40,
            'price' => 75000,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'registration_code' => 'PDG-STATUS-001',
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '081234567890',
            'payment_status' => 'pending',
        ]);
        Payment::create([
            'registration_id' => $registration->id,
            'order_id' => 'PDG-STATUS-001',
            'amount' => 75000,
            'snap_token' => 'sandbox-snap-token',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/test',
        ]);

        $this->actingAs($owner)
            ->get(route('registrations.show', $registration))
            ->assertOk()
            ->assertSee('Lanjut ke Pembayaran')
            ->assertSee('Konfirmasi Pembayaran')
            ->assertSee('Cek Status Pembayaran')
            ->assertSee('Hasil pemeriksaan')
            ->assertDontSee('Gunakan Halaman Pembayaran');

        $this->actingAs($owner)
            ->post(route('registrations.payment.status', $registration))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('payments', [
            'registration_id' => $registration->id,
            'transaction_id' => 'midtrans-transaction-001',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ]);
        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/PDG-STATUS-001/status'));
    }

    public function test_refresh_rejects_unsigned_or_mismatched_midtrans_status_response(): void
    {
        config([
            'services.midtrans.server_key' => 'sandbox-server-key',
            'services.midtrans.is_production' => false,
        ]);

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'order_id' => 'ORDER-LAIN',
                'transaction_status' => 'settlement',
                'status_code' => '200',
                'gross_amount' => '75000.00',
                'signature_key' => 'forged',
            ]),
        ]);

        $owner = User::factory()->create(['role' => 'peserta']);
        $event = Event::create([
            'title' => 'Event Validasi Status',
            'description' => 'Pengujian respons status Midtrans.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 40,
            'price' => 75000,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'registration_code' => 'PDG-STATUS-INVALID',
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '081234567890',
            'payment_status' => 'pending',
        ]);
        Payment::create([
            'registration_id' => $registration->id,
            'order_id' => 'PDG-STATUS-INVALID',
            'amount' => 75000,
        ]);

        $this->actingAs($owner)
            ->postJson(route('registrations.payment.status', $registration))
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Respons status Midtrans tidak lolos validasi keamanan. Status pembayaran tidak diubah.']);

        $this->assertSame('pending', $registration->fresh()->payment_status);
        $this->assertSame('pending', $registration->payment->fresh()->transaction_status);
    }

    public function test_other_participant_cannot_refresh_payment_status(): void
    {
        $owner = User::factory()->create(['role' => 'peserta']);
        $otherParticipant = User::factory()->create(['role' => 'peserta']);
        $event = Event::create([
            'title' => 'Event Privat Midtrans',
            'description' => 'Status pembayaran hanya untuk pemilik.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 40,
            'price' => 75000,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'registration_code' => 'PDG-STATUS-PRIVATE',
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '081234567890',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($otherParticipant)
            ->post(route('registrations.payment.status', $registration))
            ->assertForbidden();
    }

    public function test_owner_can_retry_initializing_a_payment_after_connection_failure(): void
    {
        config([
            'services.midtrans.server_key' => 'sandbox-server-key',
            'services.midtrans.is_production' => false,
        ]);

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'retried-snap-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/retried',
            ]),
        ]);

        $owner = User::factory()->create(['role' => 'peserta']);
        $event = Event::create([
            'title' => 'Event Retry Midtrans',
            'description' => 'Event untuk pengujian ulang pembayaran.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 40,
            'price' => 75000,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'registration_code' => 'PDG-RETRY-001',
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '081234567890',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'registration_id' => $registration->id,
            'order_id' => 'PDG-FAILED-001',
            'amount' => 75000,
        ]);

        $this->actingAs($owner)
            ->get(route('registrations.show', $registration))
            ->assertOk()
            ->assertSee('Siapkan dan Lanjutkan Pembayaran');

        $this->actingAs($owner)
            ->post(route('registrations.payment.initialize', $registration))
            ->assertRedirect()
            ->assertSessionHas('status');

        $payment->refresh();

        $this->assertSame('retried-snap-token', $payment->snap_token);
        $this->assertNotSame('PDG-FAILED-001', $payment->order_id);
        $this->assertSame('https://app.sandbox.midtrans.com/snap/v4/redirection/retried', $payment->redirect_url);
    }

    public function test_retry_handles_midtrans_authentication_rejection_without_a_server_error(): void
    {
        config([
            'services.midtrans.server_key' => 'invalid-server-key',
            'services.midtrans.is_production' => false,
        ]);

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([], 401),
        ]);

        [$owner, $registration] = $this->createPendingRegistration('PDG-RETRY-AUTH');

        $this->actingAs($owner)
            ->from(route('registrations.show', $registration))
            ->post(route('registrations.payment.initialize', $registration))
            ->assertRedirect(route('registrations.show', $registration))
            ->assertSessionHas(
                'payment_error',
                'Konfigurasi autentikasi Midtrans ditolak. Silakan hubungi admin.'
            );
    }

    public function test_owner_can_initialize_payment_as_json_for_the_snap_popup(): void
    {
        config([
            'services.midtrans.server_key' => 'sandbox-server-key',
            'services.midtrans.is_production' => false,
        ]);

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'json-snap-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/json',
            ]),
        ]);

        [$owner, $registration] = $this->createPendingRegistration('PDG-RETRY-JSON');

        $this->actingAs($owner)
            ->postJson(route('registrations.payment.initialize', $registration))
            ->assertOk()
            ->assertJson([
                'message' => 'Pembayaran berhasil disiapkan.',
                'snap_token' => 'json-snap-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/json',
            ]);
    }

    public function test_retry_handles_an_unexpected_service_exception_without_a_server_error(): void
    {
        $this->mock(MidtransSnapService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTransaction')
                ->once()
                ->andThrow(new LogicException('Unexpected provider failure'));
        });

        [$owner, $registration] = $this->createPendingRegistration('PDG-RETRY-UNEXPECTED');

        $this->actingAs($owner)
            ->from(route('registrations.show', $registration))
            ->post(route('registrations.payment.initialize', $registration))
            ->assertRedirect(route('registrations.show', $registration))
            ->assertSessionHas(
                'payment_error',
                'Pembayaran belum dapat disiapkan karena terjadi gangguan pada server. Silakan coba kembali atau hubungi admin.'
            );
    }

    private function createPendingRegistration(string $registrationCode): array
    {
        $owner = User::factory()->create(['role' => 'peserta']);
        $event = Event::create([
            'title' => 'Event Retry Midtrans',
            'description' => 'Event untuk pengujian ulang pembayaran.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 40,
            'price' => 75000,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'registration_code' => $registrationCode,
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '081234567890',
            'payment_status' => 'pending',
        ]);
        Payment::create([
            'registration_id' => $registration->id,
            'order_id' => $registrationCode,
            'amount' => 75000,
        ]);

        return [$owner, $registration];
    }
}
