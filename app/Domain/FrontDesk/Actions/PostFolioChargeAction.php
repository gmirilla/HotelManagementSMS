<?php

declare(strict_types=1);

namespace App\Domain\FrontDesk\Actions;

use App\Domain\Accounting\Support\FolioLedgerPoster;
use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Domain\FrontDesk\Support\FolioBalanceCalculator;
use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PostFolioChargeAction
{
    public function __construct(
        private readonly FolioBalanceCalculator $balanceCalculator,
        private readonly FolioLedgerPoster $ledgerPoster,
    ) {}

    public function handle(Folio $folio, string $chargeType, string $description, int $amountCents, User $staff): FolioCharge
    {
        if ($folio->status !== FolioStatus::Open) {
            throw ValidationException::withMessages(['folio' => __('Charges can only be posted to an open folio.')]);
        }

        $charge = $folio->charges()->create([
            'charge_type' => $chargeType,
            'description' => $description,
            'amount_cents' => $amountCents,
            'charge_date' => now()->toDateString(),
            'posted_by_user_id' => $staff->id,
        ]);
        $charge->setRelation('folio', $folio);

        $this->balanceCalculator->recalculate($folio);
        $this->ledgerPoster->postCharge($charge, $staff);

        return $charge;
    }
}
