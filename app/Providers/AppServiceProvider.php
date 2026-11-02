<?php

namespace App\Providers;

use App\Http\Support\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    private const int REGISTRATIONS_PER_MINUTE = 10;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureLoginRateLimiter();
        $this->configureRegisterRateLimiter();
    }

    /**
     * Refuse further login attempts for 60 seconds once the same email address
     * has failed 5 times from the same caller.
     *
     * Only the `401` responses count toward the limit, so a successful login or
     * a validation failure never consumes the budget. The check still runs
     * before the request is handled, which is what refuses a sixth attempt even
     * when it carries the correct password.
     */
    private function configureLoginRateLimiter(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip())
            ->after(fn (Response $response) => $response->getStatusCode() === Response::HTTP_UNAUTHORIZED)
            ->response(fn (Request $request, array $headers) => ApiResponse::tooManyRequests(
                "Too many login attempts. Please try again in {$headers['Retry-After']} seconds."
            )->withHeaders($headers)));
    }

    /**
     * Refuse further registrations for 60 seconds once the same caller has made
     * `REGISTRATIONS_PER_MINUTE` attempts.
     *
     * Every attempt counts whatever its outcome: a success creates an account, and
     * a rejection can tell the caller that an address is already registered.
     */
    private function configureRegisterRateLimiter(): void
    {
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(self::REGISTRATIONS_PER_MINUTE)
            ->by($request->ip())
            ->response(fn (Request $request, array $headers) => ApiResponse::tooManyRequests(
                "Too many registration attempts. Please try again in {$headers['Retry-After']} seconds."
            )->withHeaders($headers)));
    }
}
