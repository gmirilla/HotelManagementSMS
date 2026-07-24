<?php

declare(strict_types=1);

/**
 * Full-page Livewire render tests for every CRM/Loyalty component, in both
 * empty and populated states. This practice exists because Action/Calculator
 * unit tests never would have caught the class of bug found in the
 * Restaurant/Inventory modules (a #[Computed] method returning the wrong
 * Collection type, which only blows up on an actual render) — see
 * RestaurantLivewireRenderTest.php for the original incident this practice
 * comes from.
 */

use App\Domain\CRM\Actions\EarnLoyaltyPointsAction;
use App\Domain\CRM\Enums\FeedbackStatus;
use App\Domain\CRM\Enums\FeedbackType;
use App\Livewire\CRM\CorporateAccountManager;
use App\Livewire\CRM\CouponManager;
use App\Livewire\CRM\FeedbackManager;
use App\Livewire\CRM\LoyaltyManager;
use App\Livewire\CRM\MarketingCampaignManager;
use App\Models\Branch;
use App\Models\CorporateAccount;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\GuestFeedback;
use App\Models\MarketingCampaign;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'crm.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('crm.manage');

    $this->branch = Branch::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('corporate account manager renders with no accounts yet', function (): void {
    Livewire::actingAs($this->staff)->test(CorporateAccountManager::class)->assertOk();
});

test('corporate account manager renders with corporate and travel-agent accounts present', function (): void {
    CorporateAccount::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    CorporateAccount::factory()->travelAgent()->create(['tenant_id' => $this->branch->tenant_id]);

    Livewire::actingAs($this->staff)->test(CorporateAccountManager::class)->assertOk();
});

test('feedback manager renders with no feedback yet', function (): void {
    Livewire::actingAs($this->staff)->test(FeedbackManager::class)->assertOk();
});

test('feedback manager renders and finds guests via search', function (): void {
    $guest = Guest::factory()->create(['tenant_id' => $this->branch->tenant_id, 'first_name' => 'Zendaya']);
    GuestFeedback::factory()->create(['branch_id' => $this->branch->id, 'guest_id' => $guest->id, 'status' => FeedbackStatus::Open, 'type' => FeedbackType::Complaint]);

    $component = Livewire::actingAs($this->staff)->test(FeedbackManager::class)->assertOk();

    $component->set('guestSearch', 'Zendaya')->assertOk();
    expect($component->get('guestResults'))->toHaveCount(1);
});

test('loyalty manager renders with no guest selected', function (): void {
    Livewire::actingAs($this->staff)->test(LoyaltyManager::class)->assertOk();
});

test('loyalty manager renders a selected guest\'s points balance and tier', function (): void {
    $guest = Guest::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    app(EarnLoyaltyPointsAction::class)->handle($guest, 6000, 'Stay reward');

    $component = Livewire::actingAs($this->staff)->test(LoyaltyManager::class)->assertOk();

    // pointsBalance/tier are render()-only view data (not component
    // properties), so they must be asserted via rendered output.
    $component->call('selectGuest', $guest->id)->assertOk()->assertSee('6,000 pts')->assertSee('Gold');
});

test('coupon manager renders with no coupons yet', function (): void {
    Livewire::actingAs($this->staff)->test(CouponManager::class)->assertOk();
});

test('coupon manager renders with a coupon present', function (): void {
    Coupon::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->staff)->test(CouponManager::class)->assertOk();
});

test('marketing campaign manager renders with no campaigns yet', function (): void {
    Livewire::actingAs($this->staff)->test(MarketingCampaignManager::class)->assertOk();
});

test('marketing campaign manager renders with a campaign present', function (): void {
    MarketingCampaign::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->staff)->test(MarketingCampaignManager::class)->assertOk();
});
