<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottlePasswordResetRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $email = strtolower(trim((string) $request->input('email')));
        $ipKey = 'password-reset:ip:'.$request->ip();
        $emailKey = 'password-reset:email:'.hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($emailKey, 5)) {
            return redirect()->route('password.request')
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Terlalu banyak permintaan. Silakan coba kembali beberapa menit lagi.'])
                ->setStatusCode(429);
        }

        RateLimiter::hit($ipKey, 900);
        RateLimiter::hit($emailKey, 900);

        return $next($request);
    }
}
