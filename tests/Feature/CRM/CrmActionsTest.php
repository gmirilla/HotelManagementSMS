<?php

declare(strict_types=1);

use App\Domain\CRM\Actions\AssignGuestFeedbackAction;
use App\Domain\CRM\Actions\EarnLoyaltyPointsAction;
use App\Domain\CRM\Actions\LogGuestFeedbackAction;
use App\Domain\CRM\Actions\RedeemCouponAction;
use App\Domain\CRM\Actions\RedeemLoyaltyPointsAction;
use App\Domain\CRM\Actions\ResolveGuestFeedbackAction;
use App\Domain\CRM\Enums\CouponScope;
use App\Domain\CRM\Enums\FeedbackStatus;
use App\Domain\CRM\Enums\FeedbackType;
use App\Models\Branch;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\LoyaltyAccount;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('logging feedback creates an open record', function (): void {
    $branch = Branch::factory()->create();
    $guest = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);

    $feedback = app(LogGuestFeedbackAction::class)->handle($branch, $guest, FeedbackType::Complaint, 'Cold breakfast', 'The eggs were cold.');

    expect($feedback->status)->toBe(FeedbackStatus::Open)
        ->and($feedback->guest_id)->toBe($guest->id);
});

test('assigning feedback moves it to in progress', function (): void {
    $branch = Branch::factory()->create();
    $feedback = app(LogGuestFeedbackAction::class)->handle($branch, null, FeedbackType::Suggestion, 'Subject', 'Description');
    $assignee = User::factory()->create();

    app(AssignGuestFeedbackAction::class)->handle($feedback, $assignee);

    expect($feedback->fresh()->status)->toBe(FeedbackStatus::InProgress)
        ->and($feedback->fresh()->assigned_to_user_id)->toBe($assignee->id);
});

test('a closed feedback record cannot be reassigned', function (): void {
    $branch = Branch::factory()->create();
    $feedback = app(LogGuestFeedbackAction::class)->handle($branch, null, FeedbackType::Complaint, 'Subject', 'Description');
    $feedback->update(['status' => FeedbackStatus::Closed]);

    app(AssignGuestFeedbackAction::class)->handle($feedback, User::factory()->create());
})->throws(ValidationException::class);

test('resolving feedback records notes and a timestamp', function (): void {
    $branch = Branch::factory()->create();
    $feedback = app(LogGuestFeedbackAction::class)->handle($branch, null, FeedbackType::Complaint, 'Subject', 'Description');

    app(ResolveGuestFeedbackAction::class)->handle($feedback, 'Compensated the guest.');

    expect($feedback->fresh()->status)->toBe(FeedbackStatus::Resolved)
        ->and($feedback->fresh()->resolution_notes)->toBe('Compensated the guest.')
        ->and($feedback->fresh()->resolved_at)->not->toBeNull();
});

test('earning loyalty points auto-enrolls the guest', function (): void {
    $guest = Guest::factory()->create();

    expect(LoyaltyAccount::where('guest_id', $guest->id)->exists())->toBeFalse();

    app(EarnLoyaltyPointsAction::class)->handle($guest, 500, 'Stay reward');

    expect(LoyaltyAccount::where('guest_id', $guest->id)->exists())->toBeTrue();
});

test('earning zero or negative points is rejected', function (): void {
    app(EarnLoyaltyPointsAction::class)->handle(Guest::factory()->create(), 0, 'Invalid');
})->throws(ValidationException::class);

test('redeeming more points than the balance is rejected', function (): void {
    $guest = Guest::factory()->create();
    app(EarnLoyaltyPointsAction::class)->handle($guest, 100, 'Stay reward');
    $account = LoyaltyAccount::where('guest_id', $guest->id)->firstOrFail();

    app(RedeemLoyaltyPointsAction::class)->handle($account, 101, 'Too many points');
})->throws(ValidationException::class);

test('redeeming points within the balance stores a negative transaction', function (): void {
    $guest = Guest::factory()->create();
    app(EarnLoyaltyPointsAction::class)->handle($guest, 100, 'Stay reward');
    $account = LoyaltyAccount::where('guest_id', $guest->id)->firstOrFail();

    $transaction = app(RedeemLoyaltyPointsAction::class)->handle($account, 40, 'Redeemed for a drink voucher');

    expect($transaction->points)->toBe(-40);
});

test('redeeming an inactive coupon is rejected', function (): void {
    $coupon = Coupon::factory()->create(['is_active' => false]);

    app(RedeemCouponAction::class)->handle($coupon, CouponScope::All, 10000);
})->throws(ValidationException::class);

test('redeeming a coupon outside its validity window is rejected', function (): void {
    $coupon = Coupon::factory()->create(['valid_from' => now()->subMonths(2)->toDateString(), 'valid_until' => now()->subMonth()->toDateString()]);

    app(RedeemCouponAction::class)->handle($coupon, CouponScope::All, 10000);
})->throws(ValidationException::class);

test('redeeming a coupon that has reached its usage limit is rejected', function (): void {
    $coupon = Coupon::factory()->create(['usage_limit' => 1]);
    app(RedeemCouponAction::class)->handle($coupon, CouponScope::All, 10000);

    app(RedeemCouponAction::class)->handle($coupon, CouponScope::All, 10000);
})->throws(ValidationException::class);

test('redeeming a coupon outside its scope is rejected', function (): void {
    $coupon = Coupon::factory()->create(['scope' => CouponScope::Room]);

    app(RedeemCouponAction::class)->handle($coupon, CouponScope::Restaurant, 10000);
})->throws(ValidationException::class);

test('a percent coupon discounts a percentage of the base amount', function (): void {
    $coupon = Coupon::factory()->create(['discount_type' => 'percent', 'discount_value' => 10, 'scope' => CouponScope::All]);

    $redemption = app(RedeemCouponAction::class)->handle($coupon, CouponScope::Room, 10000);

    expect($redemption->discount_applied_cents)->toBe(1000);
});

test('a fixed coupon never discounts more than the base amount', function (): void {
    $coupon = Coupon::factory()->create(['discount_type' => 'fixed', 'discount_value' => 5000, 'scope' => CouponScope::All]);

    $redemption = app(RedeemCouponAction::class)->handle($coupon, CouponScope::Room, 3000);

    expect($redemption->discount_applied_cents)->toBe(3000);
});
