<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Enums;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    /**
     * Whether a debit to this account type increases (true) or decreases
     * (false) its balance — the standard double-entry convention.
     */
    public function increasesOnDebit(): bool
    {
        return match ($this) {
            self::Asset, self::Expense => true,
            self::Liability, self::Equity, self::Revenue => false,
        };
    }
}
