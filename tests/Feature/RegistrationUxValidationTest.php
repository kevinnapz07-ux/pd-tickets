<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationUxValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_gmail_when_email_field_is_configured(): void
    {
        $participant = $this->participant();
        $event = $this->event();

        $response = $this->followingRedirects()
            ->actingAs($participant)
            ->from(route('events.show', $event))
            ->post(route('registrations.store', $event), [
                'participant_type' => 'umum',
                'name' => 'Peserta Validasi',
                'email' => 'peserta@yahoo.com',
                'phone' => '081234567890',
                'gender' => 'laki_laki',
                'domicile' => 'Depok',
            ]);

        $response
            ->assertOk()
            ->assertSee('Email peserta wajib menggunakan alamat Gmail (@gmail.com).')
            ->assertSee('id="registration_umum_email_error"', false)
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_registration_rejects_unreasonable_indonesian_phone_number(): void
    {
        $participant = $this->participant();
        $event = $this->event();

        $this->actingAs($participant)
            ->post(route('registrations.store', $event), [
                'participant_type' => 'umum',
                'name' => 'Peserta Validasi',
                'email' => 'peserta.validasi@gmail.com',
                'phone' => '12345',
                'gender' => 'perempuan',
                'domicile' => 'Jakarta',
            ])
            ->assertSessionHasErrors([
                'phone' => 'Nomor WhatsApp harus memakai format Indonesia yang valid, misalnya 081234567890 atau 6281234567890.',
            ]);
    }

    public function test_registration_does_not_require_email_or_phone_when_schema_omits_them(): void
    {
        $participant = $this->participant();
        $event = $this->event([
            'registration_form_schema' => [[
                'key' => 'relawan',
                'label' => 'Relawan',
                'fields' => ['name', 'ministry_interest'],
            ]],
        ]);

        $this->actingAs($participant)
            ->post(route('registrations.store', $event), [
                'participant_type' => 'relawan',
                'name' => 'Peserta Dinamis',
                'ministry_interest' => 'Musik',
            ])
            ->assertRedirect();

        $registration = Registration::query()->firstOrFail();
        $this->assertSame($participant->email, $registration->email);
        $this->assertSame('-', $registration->phone);
        $this->assertSame('Musik', $registration->custom_fields['ministry_interest']);
    }

    public function test_registration_form_has_frontend_constraints_and_loading_state(): void
    {
        $participant = $this->participant();
        $event = $this->event();

        $this->actingAs($participant)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('data-gmail-field', false)
            ->assertSee('data-indonesian-phone', false)
            ->assertSee('pattern="[A-Za-z0-9._%+\-]+@gmail\.com"', false)
            ->assertSee('data-registration-submit', false)
            ->assertSee('class="button-spinner"', false)
            ->assertSee('required-mark', false);
    }

    private function participant(): User
    {
        return User::forceCreate([
            'name' => 'Peserta Prompt Tiga',
            'email' => 'peserta.prompt3@gmail.com',
            'password' => 'password',
            'role' => 'peserta',
        ]);
    }

    private function event(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Event Prompt Tiga',
            'description' => 'Event untuk menguji penyempurnaan registrasi.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 50,
            'price' => 0,
            'is_published' => true,
            'registration_is_open' => true,
        ], $attributes));
    }
}
