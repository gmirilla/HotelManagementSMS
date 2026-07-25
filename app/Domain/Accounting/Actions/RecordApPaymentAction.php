<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\Enums\ApStatus;
use App\Domain\Accounting\Support\CorporateLedgerPoster;
use App\Models\ApEntry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RecordApPaymentAction
{
    public function __construct(private readonly CorporateLedgerPoster $ledgerPoster) {}

    public function handle(ApEntry $entry, int $amountCents, ?User $staff = null): ApEntry
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

        $this->ledgerPoster->postApPayment($entry, $amountCents, $staff);

        return $entry;
    }
}
