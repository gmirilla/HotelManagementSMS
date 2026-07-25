<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Support;

use App\Models\Account;
use RuntimeException;

/**
 * Resolves a branch's chart-of-accounts entry by code (FR-ACC-001: the
 * chart is branch-scoped, not global) — shared by every *LedgerPoster so a
 * branch missing an expected account fails loudly and identically
 * everywhere, rather than silently skipping a posting or crashing on a
 * null model.
 */
class BranchAccountResolver
{
    public function resolve(int $branchId, string $code): Account
    {
        $account = Account::where('branch_id', $branchId)->where('code', $code)->first();

        if (! $account) {
            throw new RuntimeException("Branch {$branchId} has no chart-of-accounts entry for code {$code} — add one under Chart of Accounts before posting.");
        }

        return $account;
    }
}
