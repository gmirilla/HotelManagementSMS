<?php

declare(strict_types=1);

namespace App\Domain\CRM\Actions;

use App\Domain\CRM\Enums\LoyaltyTransactionType;
use App\Models\Guest;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EarnLoyaltyPointsAction
{
    public function handle(Guest $guest, int $points, string $description, ?Model $reference = null): LoyaltyTransaction
    {
        if ($points <= 0) {
            throw ValidationException::withMessages(['points' => __('Points earned must be greater than zero.')]);
        }

        $account = LoyaltyAccount::firstOrCreate(['guest_id' => $guest->id], ['enrolled_at' => now()->toDateString()]);

        return $account->transactions()->create([
            'transaction_type' => LoyaltyTransactionType::Earn,
            'points' => $points,
            'description' => $description,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'transaction_date' => now()->toDateString(),
        ]);
    }
}
