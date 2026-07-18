<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'redirect' => ['nullable', 'string', 'max:500'],
        ]);

        $remember = $request->boolean('remember');
        $redirect = $credentials['redirect'] ?? null;
        unset($credentials['redirect']);

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password yang Anda masukkan tidak valid.']);
        }

        $request->session()->regenerate();
        RateLimiter::clear('auth:public-login|'.$request->ip());
        $user = $request->user();
        $message = 'Login berhasil. Selamat datang, '.$user->name.'.';

        if ($user->role === 'admin') {
            return redirect()->route('filament.admin.pages.dashboard')->with('status', $message);
        }

        $destination = $this->safeInternalRedirect($request, $redirect)
            ?? $this->safeInternalRedirect($request, $request->session()->pull('url.intended'));

        if ($destination) {
            return redirect()->to($destination)->with('status', $message);
        }

        return redirect()->route('events.index')->with('status', $message);
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:120', 'ends_with:@gmail.com', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.ends_with' => 'Gunakan alamat Gmail yang valid.',
            'email.email' => 'Gunakan alamat Gmail yang valid.',
        ]);

        $user = DB::transaction(fn (): User => User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'peserta',
        ]));

        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('events.index')
            ->with('status', 'Akun berhasil dibuat dan sudah aktif. Selamat datang, '.$user->name.'.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:120'],
        ]);

        try {
            Password::sendResetLink(['email' => $data['email']]);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('password.request')
            ->with('status', 'Jika alamat email tersebut terdaftar, kami telah mengirimkan tautan untuk mengatur ulang password. Silakan periksa Inbox maupun folder Spam.');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password berhasil diperbarui. Silakan login kembali.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('events.index')->with('status', 'Anda sudah keluar.');
    }

    private function safeInternalRedirect(Request $request, ?string $redirect): ?string
    {
        if (! $redirect) {
            return null;
        }

        $isRelativePath = str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//');
        $path = parse_url($redirect, PHP_URL_PATH) ?: '/';

        if (str_starts_with($path, '/admin') || str_starts_with($path, '/filament-admin')) {
            return null;
        }

        if ($isRelativePath) {
            return $redirect;
        }

        $redirectOrigin = sprintf(
            '%s://%s%s',
            parse_url($redirect, PHP_URL_SCHEME),
            parse_url($redirect, PHP_URL_HOST),
            parse_url($redirect, PHP_URL_PORT) ? ':'.parse_url($redirect, PHP_URL_PORT) : '',
        );

        $requestOrigin = $request->getSchemeAndHttpHost();

        return hash_equals($requestOrigin, $redirectOrigin) ? $redirect : null;
    }
}
