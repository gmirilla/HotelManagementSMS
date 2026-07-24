<?php

declare(strict_types=1);

namespace App\Domain\CRM\Actions;

use App\Domain\CRM\Enums\LoyaltyTransactionType;
use App\Domain\CRM\Support\LoyaltyBalanceCalculator;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use Illuminate\Validation\ValidationException;

class RedeemLoyaltyPointsAction
{
    public function __construct(private readonly LoyaltyBalanceCalculator $balanceCalculator) {}

    public function handle(LoyaltyAccount $account, int $points, string $description): LoyaltyTransaction
    {
        if ($points <= 0) {
            throw ValidationException::withMessages(['points' => __('Points to redeem must be greater than zero.')]);
        }

        if ($points > $this->balanceCalculator->pointsBalance($account)) {
            throw ValidationException::withMessages(['points' => __('The guest does not have enough points for this redemption.')]);
        }

        return $account->transactions()->create([
            'transaction_type' => LoyaltyTransactionType::Redeem,
            'points' => -$points,
            'description' => $description,
            'transaction_date' => now()->toDateString(),
        ]);
    }
}
