<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\Enums\ApStatus;
use App\Models\ApEntry;
use Illuminate\Validation\ValidationException;

class RecordApPaymentAction
{
    public function handle(ApEntry $entry, int $amountCents): ApEntry
    {
        if ($entry->status === ApStatus::Disputed) {
            throw ValidationException::withMessages(['status' => __('A disputed payable cannot be paid until the dispute is resolved.')]);
        }

        if ($amountCents <= 0 || $amountCents > $entry->outstandingCents()) {
            throw ValidationException::withMessages(['amount' => __('Payment amount must be positive and not exceed the outstanding balance.')]);
        }

        $newPaidCents = $entry->paid_cents + $amountCents;

        $entry->update([
            'paid_cents' => $newPaidCents,
            'status' => $newPaidCents >= $entry->amount_cents ? ApStatus::Paid : ApStatus::PartiallyPaid,
        ]);

        return $entry;
    }
}
