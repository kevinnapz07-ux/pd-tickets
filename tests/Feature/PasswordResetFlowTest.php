<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    private const SUCCESS_MESSAGE = 'Jika alamat email tersebut terdaftar, kami telah mengirimkan tautan untuk mengatur ulang password. Silakan periksa Inbox maupun folder Spam.';

    public function test_registered_and_unknown_email_receive_the_same_safe_response(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'peserta@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', self::SUCCESS_MESSAGE)
            ->assertSessionMissing('reset_link');

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'tidak-ada@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', self::SUCCESS_MESSAGE)
            ->assertSessionMissing('reset_link');

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
    }

    public function test_reset_email_uses_pdug_branding_and_laravel_reset_url(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'peserta@example.com']);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
            $message = $notification->toMail($user);

            return $message->subject === 'Reset Password Akun PD Gunadarma'
                && $message->actionText === 'Atur Ulang Password'
                && str_contains($message->actionUrl, route('password.reset', $notification->token, false));
        });
    }

    public function test_password_reset_request_is_limited_by_ip_and_email(): void
    {
        $this->app['auth']->guard()->logout();
        Notification::fake();
        $user = User::factory()->create(['email' => 'peserta@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.email'), ['email' => $user->email])
                ->assertRedirect(route('password.request'))
                ->assertSessionHas('status', self::SUCCESS_MESSAGE);
        }

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertStatus(429)
            ->assertSessionHasErrors('email', 'Terlalu banyak permintaan. Silakan coba kembali beberapa menit lagi.');
    }

    public function test_password_can_be_reset_with_laravel_token_and_used_to_login(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'peserta@example.com',
            'password' => 'password-lama',
        ]);
        $token = null;

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-baru',
        ])->assertRedirect(route('events.index'));
    }

    public function test_expired_reset_token_cannot_change_the_password(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'peserta@example.com',
            'password' => 'password-lama',
        ]);
        $token = null;

        $this->post(route('password.email'), ['email' => $user->email]);
        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        DB::table('password_reset_tokens')->where('email', $user->email)->update([
            'created_at' => now()->subMinutes(config('auth.passwords.users.expire') + 1),
        ]);

        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }
}
