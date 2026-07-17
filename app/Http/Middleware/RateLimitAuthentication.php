<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitAuthentication
{
    public function handle(Request $request, Closure $next, string $bucket): Response
    {
        $key = 'auth:'.$bucket.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return redirect()->route('login')
                ->setStatusCode(429)
                ->header('Retry-After', (string) RateLimiter::availableIn($key))
                ->withErrors(['email' => 'Terlalu banyak percobaan. Silakan coba kembali nanti.']);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
