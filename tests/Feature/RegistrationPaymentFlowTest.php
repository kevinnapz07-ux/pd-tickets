<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_and_participant_only_sees_own_registrations(): void
    {
        [$owner, $registration] = $this->registration();
        [$other, $otherRegistration] = $this->registration();

        $this->get(route('registrations.index'))->assertRedirect(route('login'));
        $response = $this->actingAs($owner)->get(route('registrations.index'))->assertOk();

        $response->assertSee($registration->registration_code)
            ->assertDontSee($otherRegistration->registration_code);
        $this->actingAs($other)
            ->get(route('registrations.payment.state', $registration))
            ->assertForbidden();
    }

    public function test_pending_payment_is_reused_and_local_polling_does_not_call_midtrans(): void
    {
        Http::fake();
        [$owner, $registration, $payment] = $this->registration();
        $payment->update(['snap_token' => 'existing-token']);

        $this->actingAs($owner)
            ->postJson(route('registrations.payment.initialize', $registration))
            ->assertOk()
            ->assertJsonPath('snap_token', 'existing-token');

        $this->actingAs($owner)
            ->getJson(route('registrations.payment.state', $registration))
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('ticket_url', null);

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame($payment->order_id, $registration->fresh()->payment->order_id);
        Http::assertNothingSent();
    }

    public function test_terminal_payment_can_be_retried_once_without_new_registration(): void
    {
        config(['services.midtrans.server_key' => 'sandbox-server-key']);
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'new-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/new',
            ]),
        ]);
        [$owner, $registration, $oldPayment] = $this->registration('expired');

        $this->actingAs($owner)
            ->post(route('registrations.payment.retry', $registration))
            ->assertRedirect(route('registrations.show', $registration));

        $registration->refresh();
        $this->assertSame('pending', $registration->payment_status);
        $this->assertDatabaseCount('registrations', 1);
        $this->assertDatabaseCount('payments', 2);
        $this->assertNotSame($oldPayment->order_id, $registration->payment->order_id);
        $this->assertSame('new-token', $registration->payment->snap_token);

        $this->actingAs($owner)->post(route('registrations.payment.retry', $registration));
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_new_registration_code_is_short_unambiguous_and_qr_token_stays_long(): void
    {
        [$owner] = $this->registration();
        $event = Event::create([
            'title' => 'Workshop Nasional',
            'description' => 'Kode singkat',
            'location' => 'Depok',
            'starts_at' => now()->addWeek(),
            'quota' => 20,
            'price' => 0,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'name' => $owner->name,
            'email' => 'new@example.test',
            'phone' => '0812',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{1,4}-[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/', $registration->registration_code);
        $this->assertSame(64, strlen($registration->verification_token));
    }

    public function test_ticket_list_uses_real_check_in_status_and_detail_has_no_self_link(): void
    {
        $owner = User::factory()->create(['role' => 'peserta']);
        $waiting = $this->paidTicket($owner, 'Waiting Ticket', null);
        $checkedIn = $this->paidTicket($owner, 'Checked Ticket', now());
        $pending = $this->paidTicket($owner, 'Pending Ticket', null);
        $pending->event->update(['price' => 50000]);
        $pending->update(['payment_status' => 'pending', 'registration_status' => null]);
        $otherOwner = User::factory()->create(['role' => 'peserta']);
        $otherTicket = $this->paidTicket($otherOwner, 'Other Ticket', null);

        $ticketList = $this->actingAs($owner)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Tiket Saya')
            ->assertSee('Pantau registrasi, pembayaran, dan tiket event dari satu tempat.')
            ->assertSee('Siap Check-in')
            ->assertSee('Sudah Check-in')
            ->assertSee('Menunggu Pembayaran')
            ->assertSee($pending->registration_code)
            ->assertSee('Lanjutkan Pembayaran')
            ->assertSee('Tampilkan QR')
            ->assertSee('Lihat Status')
            ->assertSee('Lihat Detail')
            ->assertSee('QR Check-in')
            ->assertSee('Tunjukkan QR ini kepada panitia saat hadir.')
            ->assertSee('Check-in Berhasil')
            ->assertSee('Tiket ini sudah digunakan.')
            ->assertSee($checkedIn->checked_in_at->translatedFormat('d M Y, H:i').' WIB')
            ->assertSee('data-ticket-modal', false)
            ->assertSee('data-ticket-modal-open', false)
            ->assertSee('QR Check-in '.$waiting->registration_code)
            ->assertDontSee('QR Check-in '.$checkedIn->registration_code)
            ->assertDontSee('QR Check-in '.$pending->registration_code)
            ->assertSee(route('registrations.show', $waiting), false)
            ->assertSee(route('registrations.show', $pending), false)
            ->assertDontSee($otherTicket->registration_code)
            ->assertDontSee($owner->email)
            ->assertDontSee($waiting->phone)
            ->assertDontSee('Order ID')
            ->assertDontSee('Lihat Tiket')
            ->assertDontSee('Akun Peserta')
            ->assertDontSee('>Berhasil<', false);

        $this->assertSame(1, substr_count($ticketList->getContent(), 'data:image/svg+xml;base64,'));

        $this->actingAs($owner)
            ->get(route('registrations.show', $waiting))
            ->assertOk()
            ->assertSee('registration-detail-layout', false)
            ->assertSee('detail-main registration-detail-main', false)
            ->assertSee('form-panel registration-detail-panel', false)
            ->assertSee('Maps Lokasi')
            ->assertSee('<dt>Tanggal</dt>', false)
            ->assertSee('<dt>Waktu</dt>', false)
            ->assertSee('<dt>Lokasi</dt>', false)
            ->assertSee('Lunas')
            ->assertSee('Terdaftar')
            ->assertSee('Siap Check-in')
            ->assertSee('Kode Registrasi')
            ->assertSee('Detail Peserta')
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('data-accordion-panel hidden', false)
            ->assertDontSee('Status Transaksi')
            ->assertDontSee('Status Pendaftaran')
            ->assertDontSee('Order ID')
            ->assertSee('Kembali ke Beranda')
            ->assertDontSee('Lihat Tiket');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('registrations.show', $waiting))
            ->assertOk()
            ->assertSee($waiting->email)
            ->assertSee($waiting->phone);

        $this->assertNotNull($checkedIn->checked_in_at);
    }

    private function registration(string $status = 'pending'): array
    {
        $owner = User::factory()->create(['role' => 'peserta']);
        $event = Event::create([
            'title' => 'Event Payment '.fake()->unique()->numberBetween(1, 999999),
            'description' => 'Payment flow',
            'location' => 'Depok',
            'starts_at' => now()->addWeek(),
            'quota' => 20,
            'price' => 50000,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '0812',
            'payment_status' => $status,
        ]);
        $payment = Payment::create([
            'registration_id' => $registration->id,
            'order_id' => 'ORDER-'.fake()->unique()->uuid(),
            'amount' => 50000,
            'transaction_status' => $status === 'expired' ? 'expire' : 'pending',
        ]);

        return [$owner, $registration, $payment];
    }

    private function paidTicket(User $owner, string $title, mixed $checkedInAt): Registration
    {
        $event = Event::create([
            'title' => $title,
            'description' => 'Ticket display',
            'location' => 'Depok',
            'starts_at' => now()->addWeek(),
            'quota' => 20,
            'price' => 0,
        ]);

        return Registration::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '0812',
            'payment_status' => 'paid',
            'registration_status' => $checkedInAt ? 'checked_in' : 'registered',
            'checked_in_at' => $checkedInAt,
        ]);
    }
}
