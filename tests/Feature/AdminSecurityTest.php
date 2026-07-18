<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_admin_urls_redirect_to_public_home(): void
    {
        $this->get('/admin')->assertRedirect(route('events.index'));
        $this->get('/admin/events')->assertRedirect(route('events.index'));
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Login Admin');
        $this->get('/filament-admin')->assertRedirect(route('events.index'));
        $this->get('/filament-admin/events')->assertRedirect(route('events.index'));
    }

    public function test_admin_login_only_accepts_admin_accounts(): void
    {
        $participant = User::factory()->create([
            'email' => 'public-account@example.com',
            'password' => 'password-benar',
            'role' => 'peserta',
        ]);
        $admin = User::factory()->create([
            'email' => 'admin-account@example.com',
            'password' => 'password-benar',
            'role' => 'admin',
        ]);

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => $participant->email,
            'password' => 'password-benar',
        ])->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors([
                'email' => 'Email atau password yang Anda masukkan tidak valid.',
            ]);
        $this->assertGuest();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password-benar',
        ])->assertRedirect(route('filament.admin.pages.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_participant_cannot_access_filament_resources_or_reports(): void
    {
        $participant = User::factory()->create(['role' => 'peserta']);

        $this->actingAs($participant)->get('/admin')->assertRedirect(route('events.index'));
        $this->actingAs($participant)->get('/admin/users')->assertRedirect(route('events.index'));
        $this->actingAs($participant)->get('/admin/events')->assertRedirect(route('events.index'));
        $this->actingAs($participant)->get('/admin/laporan')->assertRedirect(route('events.index'));
        $this->actingAs($participant)->get('/admin/laporan/export')->assertRedirect(route('events.index'));
    }

    public function test_admin_can_access_panel_and_responses_are_not_cacheable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertHeader('Pragma', 'no-cache');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_public_login_rejects_admin_and_participant_cannot_reach_admin_panel(): void
    {
        $participant = User::factory()->create([
            'email' => 'peserta-admin-login@example.com',
            'password' => 'password-benar',
            'role' => 'peserta',
        ]);
        $admin = User::factory()->create([
            'email' => 'admin-login@example.com',
            'password' => 'password-benar',
            'role' => 'admin',
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password-benar',
            'redirect' => route('events.index'),
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Email atau password yang Anda masukkan tidak valid.',
            ]);
        $this->assertGuest();

        $this->actingAs($participant)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('events.index'));
    }

    public function test_admin_logout_invalidates_access_and_redirects_home(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('events.index'));

        $this->assertGuest();
        $this->get('/admin')->assertRedirect(route('events.index'));
    }

    public function test_public_registration_ignores_injected_admin_role(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Peserta Aman',
            'email' => 'peserta.aman@gmail.com',
            'password' => 'password-benar',
            'password_confirmation' => 'password-benar',
            'role' => 'admin',
            'email_verified_at' => now(),
        ])->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'peserta.aman@gmail.com',
            'role' => 'peserta',
        ]);
    }

    public function test_repeated_participant_login_is_rate_limited(): void
    {
        RateLimiter::clear('auth:public-login|127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => 'tidak-ada@example.com',
                'password' => 'password-salah',
            ])->assertRedirect(route('login'));
        }

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'tidak-ada@example.com',
            'password' => 'password-salah',
        ])->assertTooManyRequests();
    }
}
