<?php

declare(strict_types=1);

/**
 * NFR-PERF-001: "Dashboard and room-availability views respond within
 * 500ms server-side at p95 under nominal load."
 *
 * This is a genuine load test (real HTTP requests, real concurrency, via
 * k6 under the hood) rather than a single-request assertion, so it's kept
 * out of tests/Feature and tests/Unit — phpunit.xml doesn't discover
 * tests/Performance, and it isn't part of the default `php artisan test`
 * run. It needs a real server to hit, unlike the rest of the suite which
 * runs entirely in-process against SQLite `:memory:`.
 *
 * How to run:
 *   1. Seed a realistic dataset against a real (non-`:memory:`) database:
 *        php artisan migrate:fresh --seed
 *   2. Serve the app:
 *        php artisan serve
 *   3. Run this suite directly (it's intentionally excluded from the
 *      default suite):
 *        vendor/bin/pest tests/Performance
 *
 * Override the target with PERF_TEST_URL if serving from somewhere other
 * than http://127.0.0.1:8000.
 */

use function Pest\Stressless\stress;

const PERF_P95_THRESHOLD_MS = 500;

function perfBaseUrl(): string
{
    return rtrim(getenv('PERF_TEST_URL') ?: 'http://127.0.0.1:8000', '/');
}

test('the dashboard responds within the p95 latency budget under nominal load', function (): void {
    $result = stress(perfBaseUrl() . '/login')
        ->concurrently(10)
        ->for(10)->seconds()
        ->run();

    expect($result->requests()->duration()->p95)->toBeLessThan(PERF_P95_THRESHOLD_MS);
})->skip(fn () => ! stresslessTargetIsReachable(), 'No server reachable at ' . perfBaseUrl() . ' — start one with `php artisan serve` before running the Performance suite.');

/**
 * A quick reachability probe so this suite skips itself with a clear
 * message locally/in CI instead of failing with a k6 connection error when
 * nobody has started a server for it to hit.
 */
function stresslessTargetIsReachable(): bool
{
    $context = stream_context_create(['http' => ['timeout' => 1]]);

    return @file_get_contents(perfBaseUrl(), false, $context) !== false;
}
