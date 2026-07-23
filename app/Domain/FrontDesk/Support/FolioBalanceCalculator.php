<?php

declare(strict_types=1);

namespace App\Domain\FrontDesk\Support;

use App\Models\Folio;

/**
 * The folio balance is always recomputed from its charges/payments rather
 * than incremented in place, so a missed edge case in one write path can
 * never leave the stored balance permanently out of sync with its ledger.
 */
class FolioBalanceCalculator
{
    public function recalculate(Folio $folio): void
    {
        $chargesTotal = (int) $folio->charges()->sum('amount_cents');
        $paymentsTotal = (int) $folio->payments()->where('status', 'completed')->sum('amount_cents');

        $folio->update(['balance_cents' => $chargesTotal - $paymentsTotal]);
    }
}
