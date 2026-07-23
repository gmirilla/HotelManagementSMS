<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\Enums\ArStatus;
use App\Models\ArEntry;
use Illuminate\Validation\ValidationException;

class RecordArPaymentAction
{
    public function handle(ArEntry $entry, int $amountCents): ArEntry
    {
        if ($entry->status === ArStatus::WrittenOff) {
            throw ValidationException::withMessages(['status' => __('A written-off receivable cannot receive payments.')]);
        }

        if ($amountCents <= 0 || $amountCents > $entry->outstandingCents()) {
            throw ValidationException::withMessages(['amount' => __('Payment amount must be positive and not exceed the outstanding balance.')]);
        }

        $newPaidCents = $entry->paid_cents + $amountCents;

        $entry->update([
            'paid_cents' => $newPaidCents,
            'status' => $newPaidCents >= $entry->amount_cents ? ArStatus::Paid : ArStatus::PartiallyPaid,
        ]);

        return $entry;
    }
}
