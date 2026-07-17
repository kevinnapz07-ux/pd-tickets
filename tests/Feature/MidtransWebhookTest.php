<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\EventTicketNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => 'webhook-secret']);
    }

    public function test_valid_settlement_webhook_updates_status_and_emails_ticket_to_account_once(): void
    {
        Notification::fake();
        [$account, $registration, $payment] = $this->paymentFixture();
        $payload = $this->payload($payment);

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $stalePayload = $payload;
        $stalePayload['transaction_status'] = 'pending';
        $this->postJson(route('payments.midtrans.notification'), $stalePayload)->assertOk();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);
        $this->assertNotNull($payment->fresh()->ticket_email_sent_at);
        $this->assertSame('settlement', $payment->fresh()->transaction_status);

        Notification::assertSentToTimes($account, EventTicketNotification::class, 1);
        Notification::assertSentTo(
            $account,
            EventTicketNotification::class,
            fn (EventTicketNotification $notification, array $channels, User $notifiable): bool => $notifiable->email === 'akun-terverifikasi@example.com'
                && $notifiable->email !== $registration->email
                && $notification->registration->is($registration)
        );
    }

    public function test_invalid_signature_or_amount_does_not_change_payment_status(): void
    {
        Notification::fake();
        [$account, $registration, $payment] = $this->paymentFixture();
        $invalidSignature = $this->payload($payment);
        $invalidSignature['signature_key'] = 'forged';

        $this->postJson(route('payments.midtrans.notification'), $invalidSignature)->assertForbidden();

        $wrongAmount = $this->payload($payment, '1.00');
        $this->postJson(route('payments.midtrans.notification'), $wrongAmount)->assertStatus(422);

        $this->assertSame('pending', $registration->fresh()->payment_status);
        $this->assertSame('pending', $payment->fresh()->transaction_status);
        Notification::assertNothingSent();
    }

    public function test_webhook_signature_is_rejected_when_server_key_is_not_configured(): void
    {
        [, $registration, $payment] = $this->paymentFixture();
        config(['services.midtrans.server_key' => '']);

        $payload = [
            'order_id' => $payment->order_id,
            'status_code' => '200',
            'gross_amount' => '75000.00',
            'transaction_status' => 'settlement',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount']);

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertForbidden();
        $this->assertSame('pending', $registration->fresh()->payment_status);
    }

    public function test_ticket_is_emailed_without_requiring_email_verification(): void
    {
        Notification::fake();
        [$account, $registration, $payment] = $this->paymentFixture();

        $this->postJson(route('payments.midtrans.notification'), $this->payload($payment))->assertOk();

        $this->assertSame('paid', $registration->fresh()->payment_status);
        $this->assertNotNull($payment->fresh()->ticket_email_sent_at);
        Notification::assertSentTo($account, EventTicketNotification::class);
    }

    private function paymentFixture(): array
    {
        $account = User::factory()->state(['role' => 'peserta'])->create([
            'name' => 'Pemilik Akun',
            'email' => 'akun-terverifikasi@example.com',
        ]);
        $event = Event::create([
            'title' => 'Event Midtrans Aman',
            'description' => 'Event untuk pengujian webhook.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 40,
            'price' => 75000,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $account->id,
            'registration_code' => 'PDG-WEBHOOK-001',
            'name' => 'Nama Checkout',
            'email' => 'email-checkout@example.net',
            'phone' => '081234567890',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'registration_id' => $registration->id,
            'order_id' => 'PDG-WEBHOOK-ORDER-001',
            'amount' => 75000,
        ]);

        return [$account, $registration, $payment];
    }

    private function payload(Payment $payment, string $grossAmount = '75000.00'): array
    {
        $payload = [
            'order_id' => $payment->order_id,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_id' => 'midtrans-transaction-001',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key')
        );

        return $payload;
    }
}
