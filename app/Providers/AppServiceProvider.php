<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        $this->configureApiRateLimiting();
    }

    /**
     * FR-API-004 / NFR-SEC-004: general API traffic is capped per token (or
     * IP for unauthenticated requests); the auth endpoints get a much
     * tighter limit since they're the highest-value brute-force target.
     */
    private function configureApiRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api-auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Generous relative to the other limiters: Paystack retries webhook
        // delivery on anything but a 2xx response, and this endpoint has no
        // per-user identity to key on — only the calling IP.
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
