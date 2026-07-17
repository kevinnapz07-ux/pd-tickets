<?php

namespace App\Providers;

use App\Http\Responses\AdminLogoutResponse;
use App\Models\SiteSetting;
use App\Models\Registration;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogoutResponseContract::class, AdminLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
        URL::forceScheme('https');
        }
        
        $this->warnAboutProductionMailConfiguration();

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user?->role === 'admin') {
                Log::notice('Admin login succeeded.', [
                    'admin_id' => $event->user->getAuthIdentifier(),
                    'ip' => request()->ip(),
                ]);
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            if (request()->routeIs('login.store')) {
                Log::warning('Public login failed.', ['ip' => request()->ip()]);
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user?->role === 'admin') {
                Log::notice('Admin logout.', [
                    'admin_id' => $event->user->getAuthIdentifier(),
                    'ip' => request()->ip(),
                ]);
            }
        });

        User::updated(function (User $user): void {
            if ($user->wasChanged('role') && Auth::user()?->role === 'admin') {
                Log::notice('User role changed by admin.', [
                    'admin_id' => Auth::id(),
                    'user_id' => $user->getKey(),
                    'old_role' => $user->getOriginal('role'),
                    'new_role' => $user->role,
                ]);
            }
        });

        User::deleted(function (User $user): void {
            if (Auth::user()?->role === 'admin') {
                Log::notice('User deleted by admin.', [
                    'admin_id' => Auth::id(),
                    'user_id' => $user->getKey(),
                ]);
            }
        });

        Registration::updated(function (Registration $registration): void {
            if (Auth::user()?->role === 'admin'
                && $registration->wasChanged(['payment_status', 'registration_status', 'checked_in_at'])) {
                Log::notice('Registration status changed by admin.', [
                    'admin_id' => Auth::id(),
                    'registration_id' => $registration->getKey(),
                    'payment_status' => $registration->payment_status,
                    'registration_status' => $registration->registration_status,
                ]);
            }
        });

        SiteSetting::updated(function (SiteSetting $setting): void {
            if (Auth::user()?->role === 'admin') {
                Log::notice('Website settings changed by admin.', [
                    'admin_id' => Auth::id(),
                    'site_setting_id' => $setting->getKey(),
                    'changed_fields' => array_keys($setting->getChanges()),
                ]);
            }
        });

        view()->composer('*', function ($view): void {
            try {
                $view->with('siteSetting', SiteSetting::current());
            } catch (Throwable) {
                $view->with('siteSetting', null);
            }
        });
    }

    /**
     * Log deployment mistakes without exposing configuration values to users.
     */
    private function warnAboutProductionMailConfiguration(): void
    {
        if (! $this->app->environment('production')) {
            return;
        }

        if (config('mail.default') === 'log') {
            Log::warning('Production mailer is still set to log; transactional email will not be delivered.');
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (in_array($appHost, [null, 'localhost', '127.0.0.1'], true)) {
            Log::warning('Production APP_URL points to a local address; email links may be invalid.');
        }

        if (str_ends_with((string) config('mail.from.address'), '@example.com')) {
            Log::warning('Production MAIL_FROM_ADDRESS still uses example.com.');
        }
    }
}
