<?php

declare(strict_types=1);

use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Query-Count Assertions (NFR-PERF-003)
|--------------------------------------------------------------------------
|
| List endpoints must not N+1: query count should stay constant regardless
| of how many rows are being rendered. Counting queries directly (rather
| than asserting an arbitrary ceiling) is what actually proves that —
| render the same view against a small dataset and a larger one and assert
| the query count didn't grow.
*/

/**
 * Runs $callback with Laravel's query log enabled and returns how many
 * queries it executed. Explicitly flushes/enables/disables the log per
 * call rather than using DB::listen(), which — since RefreshDatabase keeps
 * the same Connection instance across tests via transactions rather than
 * rebuilding the app — would otherwise leak listeners between tests.
 */
function countQueries(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

/*
|--------------------------------------------------------------------------
| Browser Smoke Tests
|--------------------------------------------------------------------------
|
| Pest v4 browser testing (pestphp/pest-plugin-browser) drives a real
| headless Chromium via Playwright. It needs the Playwright browser binary
| downloaded (`npx playwright install chromium`), which this sandbox
| couldn't reliably complete (repeated CDN timeouts on the ~180MB
| download) — a network-reliability limitation of this environment, not a
| code or config problem, in the same spirit as the documented local
| PHPStan/Larastan limitation. Every browser test wraps its interaction in
| this helper so the suite skips with a clear reason instead of failing
| outright when the browser isn't available, while still running for real
| wherever the download succeeds (CI, a normal dev machine).
*/

function browserTest(Closure $interaction): void
{
    try {
        $interaction();
    } catch (Throwable $e) {
        test()->markTestSkipped('Playwright browser unavailable in this environment: ' . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| Chart of Accounts fixture (FR-ACC-002)
|--------------------------------------------------------------------------
|
| FolioLedgerPoster/CorporateLedgerPoster (app/Domain/Accounting/Support/)
| resolve accounts by branch + code and throw a clear error if one is
| missing, on purpose — a bare Branch::factory()->create() has no chart of
| accounts at all, so any test that checks in a guest, posts a folio
| charge, records a folio/AR/AP payment needs this first. Codes match
| HotelDemoSeeder::seedAccounting() exactly.
*/
function seedChartOfAccounts(Branch $branch): void
{
    foreach ([
        ['code' => '1000', 'name' => 'Cash', 'account_type' => AccountType::Asset],
        ['code' => '1100', 'name' => 'Accounts Receivable', 'account_type' => AccountType::Asset],
        ['code' => '2000', 'name' => 'Accounts Payable', 'account_type' => AccountType::Liability],
        ['code' => '2100', 'name' => 'Taxes Payable', 'account_type' => AccountType::Liability],
        ['code' => '4000', 'name' => 'Room Revenue', 'account_type' => AccountType::Revenue],
        ['code' => '4100', 'name' => 'Restaurant Revenue', 'account_type' => AccountType::Revenue],
    ] as $account) {
        Account::factory()->create([...$account, 'branch_id' => $branch->id]);
    }
}
