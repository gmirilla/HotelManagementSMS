<?php

declare(strict_types=1);

use App\Domain\CRM\Enums\LoyaltyTier;
use App\Domain\CRM\Support\LoyaltyBalanceCalculator;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;

test('points balance nets earn and redeem transactions', function (): void {
    $account = LoyaltyAccount::factory()->create();

    LoyaltyTransaction::factory()->create(['loyalty_account_id' => $account->id, 'transaction_type' => 'earn', 'points' => 500]);
    LoyaltyTransaction::factory()->create(['loyalty_account_id' => $account->id, 'transaction_type' => 'redeem', 'points' => -200]);

    expect(app(LoyaltyBalanceCalculator::class)->pointsBalance($account))->toBe(300);
});

test('lifetime points earned ignores redemptions, so tier never drops after a redemption', function (): void {
    $account = LoyaltyAccount::factory()->create();

    LoyaltyTransaction::factory()->create(['loyalty_account_id' => $account->id, 'transaction_type' => 'earn', 'points' => 6000]);
    LoyaltyTransaction::factory()->create(['loyalty_account_id' => $account->id, 'transaction_type' => 'redeem', 'points' => -5000]);

    $calculator = app(LoyaltyBalanceCalculator::class);

    expect($calculator->pointsBalance($account))->toBe(1000)
        ->and($calculator->lifetimePointsEarned($account))->toBe(6000)
        ->and($calculator->tier($account))->toBe(LoyaltyTier::Gold);
});

test('tier progression follows the lifetime-points thresholds', function (): void {
    expect(LoyaltyTier::forLifetimePoints(0))->toBe(LoyaltyTier::Silver)
        ->and(LoyaltyTier::forLifetimePoints(4999))->toBe(LoyaltyTier::Silver)
        ->and(LoyaltyTier::forLifetimePoints(5000))->toBe(LoyaltyTier::Gold)
        ->and(LoyaltyTier::forLifetimePoints(14999))->toBe(LoyaltyTier::Gold)
        ->and(LoyaltyTier::forLifetimePoints(15000))->toBe(LoyaltyTier::Platinum);
});
