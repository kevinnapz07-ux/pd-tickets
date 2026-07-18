<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketQrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_has_unique_token_and_qr_uses_public_verification_url(): void
    {
        $first = $this->ticket();
        $second = $this->ticket();

        $this->assertNotSame($first->verification_token, $second->verification_token);
        $this->assertSame(route('tickets.verify', $first->verification_token), $first->verificationUrl());
        $this->assertSame($first->verificationUrl(), $first->checkInUrl());
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $first->qrCodeDataUri());
    }

    public function test_valid_ticket_is_public_but_sensitive_data_is_hidden(): void
    {
        $ticket = $this->ticket();

        $this->get($ticket->verificationUrl())
            ->assertOk()
            ->assertSee('Tiket Siap Digunakan')
            ->assertSee('data-ticket-ready-icon', false)
            ->assertDontSee('data-ticket-checked-icon', false)
            ->assertDontSee('Status Check-in')
            ->assertSee($ticket->registration_code)
            ->assertSee($ticket->name)
            ->assertDontSee($ticket->email)
            ->assertDontSee($ticket->phone)
            ->assertDontSee($ticket->verification_token);
    }

    public function test_unknown_token_returns_friendly_not_found_page(): void
    {
        $this->get(route('tickets.verify', str_repeat('a', 64)))
            ->assertNotFound()
            ->assertSee('Tiket Tidak Valid')
            ->assertDontSee('QueryException');
    }

    public function test_only_admin_can_check_in_a_valid_ticket_once(): void
    {
        $ticket = $this->ticket();
        $participant = User::factory()->create(['role' => 'peserta']);
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = ['ticket_reference' => $ticket->verification_token];
        $this->post(route('admin.tickets.checkin'), $payload)->assertRedirect(route('events.index'));
        $this->actingAs($participant)->post(route('admin.tickets.checkin'), $payload)->assertRedirect(route('events.index'));

        $this->actingAs($admin)->post(route('admin.tickets.checkin'), $payload)->assertSessionHas('status');
        $ticket->refresh();
        $this->assertSame('checked_in', $ticket->registration_status);
        $this->assertNotNull($ticket->checked_in_at);
        $this->assertSame($admin->id, $ticket->checked_in_by);

        $this->get($ticket->verificationUrl())
            ->assertOk()
            ->assertSee('Tiket Sudah Digunakan')
            ->assertSee('data-ticket-checked-icon', false)
            ->assertDontSee('data-ticket-ready-icon', false)
            ->assertSee('Status Check-in')
            ->assertSee('Sudah check-in');

        $firstCheckIn = $ticket->checked_in_at;
        $this->actingAs($admin)->post(route('admin.tickets.checkin'), $payload)->assertSessionHasErrors('ticket_reference');
        $this->assertTrue($ticket->fresh()->checked_in_at->equalTo($firstCheckIn));
    }

    public function test_scanner_page_is_focused_and_ready_for_automatic_check_in(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.tickets.scanner'))
            ->assertOk()
            ->assertSee('Scanner QR Tiket')
            ->assertSee('Kode Tiket Manual')
            ->assertSee('data-ticket-result', false)
            ->assertSee('Memverifikasi tiket...')
            ->assertSee('Scan Lagi')
            ->assertDontSee('Administrasi Event')
            ->assertDontSee('Arahkan kamera belakang')
            ->assertDontSee('Check-in Peserta');
    }

    public function test_scanner_json_check_in_accepts_qr_url_and_returns_popup_details(): void
    {
        $ticket = $this->ticket(['registration_code' => 'IRJ-A8F29K']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.tickets.checkin'), [
                'ticket_reference' => $ticket->verificationUrl(),
            ])
            ->assertOk()
            ->assertJson([
                'message' => 'Check-in berhasil.',
                'participant_name' => $ticket->name,
                'event_name' => $ticket->event->title,
                'ticket_code' => 'IRJ-A8F29K',
            ])
            ->assertJsonStructure(['checked_in_at']);

        $this->assertNotNull($ticket->fresh()->checked_in_at);
    }

    public function test_scanner_keeps_legacy_codes_valid_and_rejects_wrong_event(): void
    {
        $legacy = $this->ticket(['registration_code' => 'PDUG-2026-LEGACY']);
        $otherEventTicket = $this->ticket();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.tickets.checkin'), [
                'ticket_reference' => $legacy->registration_code,
            ])
            ->assertOk()
            ->assertJsonPath('ticket_code', 'PDUG-2026-LEGACY');

        $this->actingAs($admin)
            ->postJson(route('admin.tickets.checkin'), [
                'ticket_reference' => $otherEventTicket->verification_token,
                'event_id' => $legacy->event_id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Tiket berasal dari event yang berbeda.');
    }

    public function test_pending_and_cancelled_tickets_cannot_check_in(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pending = $this->ticket(['payment_status' => 'pending', 'registration_status' => null]);
        $cancelled = $this->ticket(['registration_status' => 'cancelled']);

        $this->actingAs($admin)->post(route('admin.tickets.checkin'), ['ticket_reference' => $pending->registration_code])
            ->assertSessionHasErrors('ticket_reference');
        $this->actingAs($admin)->post(route('admin.tickets.checkin'), ['ticket_reference' => $cancelled->registration_code])
            ->assertSessionHasErrors('ticket_reference');

        $this->assertNull($pending->fresh()->checked_in_at);
        $this->assertNull($cancelled->fresh()->checked_in_at);
    }

    public function test_every_unsuccessful_paid_event_status_is_explained_and_cannot_check_in(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $statuses = [
            'failed' => 'Pembayaran Gagal',
            'expired' => 'Pembayaran Kedaluwarsa',
            'cancelled' => 'Pembayaran Dibatalkan',
            'refunded' => 'Pembayaran Dikembalikan',
        ];

        foreach ($statuses as $paymentStatus => $heading) {
            $ticket = $this->ticket([
                'payment_status' => $paymentStatus,
                'registration_status' => 'registered',
            ]);

            $this->get($ticket->verificationUrl())
                ->assertOk()
                ->assertSee($heading)
                ->assertDontSee('data-ticket-ready-icon', false);

            $this->actingAs($admin)
                ->post(route('admin.tickets.checkin'), ['ticket_reference' => $ticket->verification_token])
                ->assertSessionHasErrors('ticket_reference');

            $this->assertNull($ticket->fresh()->checked_in_at);
            $this->assertFalse($ticket->fresh()->isCheckInReady());
        }
    }

    private function ticket(array $attributes = []): Registration
    {
        $user = User::factory()->create(['role' => 'peserta']);
        $event = Event::create([
            'title' => 'Event QR '.fake()->unique()->numberBetween(1, 999999),
            'slug' => fake()->unique()->slug(),
            'description' => 'Event pengujian tiket QR.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 100,
            'price' => 0,
        ]);

        return Registration::create(array_merge([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '081234567890',
            'participant_type' => 'umum',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ], $attributes));
    }
}
