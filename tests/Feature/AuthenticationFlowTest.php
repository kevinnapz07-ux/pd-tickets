<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rejects_incorrect_credentials(): void
    {
        User::factory()->create([
            'email' => 'peserta@example.com',
            'password' => 'password-benar',
            'role' => 'peserta',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'peserta@example.com',
            'password' => 'password-salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Email atau password yang Anda masukkan tidak valid.',
        ]);
        $this->assertGuest();
    }

    public function test_public_login_returns_the_same_error_for_admin_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password-benar',
            'role' => 'admin',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password-benar',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Email atau password yang Anda masukkan tidak valid.',
        ]);
        $response->assertSessionMissing('status');
        $this->assertGuest();
    }

    public function test_logout_ends_the_session_and_protects_participant_profile(): void
    {
        $participant = User::factory()->create(['role' => 'peserta']);

        $this->actingAs($participant)
            ->post(route('logout'))
            ->assertRedirect(route('events.index'));

        $this->assertGuest();

        $this->get(route('participant.profile'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_change_participant_password(): void
    {
        $this->patch(route('participant.password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('login'));
    }

    public function test_participant_must_provide_the_correct_current_password(): void
    {
        $participant = User::factory()->create([
            'password' => 'password-lama',
            'role' => 'peserta',
        ]);

        $this->actingAs($participant)
            ->from(route('participant.profile'))
            ->patch(route('participant.password.update'), [
                'current_password' => 'password-salah',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect(route('participant.profile'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password-lama', $participant->fresh()->password));
    }

    public function test_participant_can_change_password_and_remains_authenticated(): void
    {
        $participant = User::factory()->create([
            'password' => 'password-lama',
            'remember_token' => 'token-lama',
            'role' => 'peserta',
        ]);

        $this->actingAs($participant)
            ->patch(route('participant.password.update'), [
                'current_password' => 'password-lama',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect(route('participant.profile'))
            ->assertSessionHas('status', 'Password berhasil diperbarui.');

        $participant->refresh();

        $this->assertAuthenticatedAs($participant);
        $this->assertTrue(Hash::check('password-baru', $participant->password));
        $this->assertFalse(Hash::check('password-lama', $participant->password));
        $this->assertNotSame('token-lama', $participant->remember_token);
    }
}
