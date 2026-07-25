<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Domain\FrontDesk\Support\FolioBalanceCalculator;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Livewire\FrontDesk\FolioShow;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @return array{0: Folio, 1: User}
 */
function makeFolioAndCashier(): array
{
    Permission::firstOrCreate(['name' => 'payments.process', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'payments.refund', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'folios.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Folio Test Cashier', 'guard_name' => 'web']);
    $role->givePermissionTo(['payments.process', 'payments.refund', 'folios.manage']);

    $tenant = Tenant::factory()->create(['paystack_secret_key' => 'sk_test_fake']);
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $guest = Guest::factory()->create(['tenant_id' => $tenant->id, 'email' => 'guest@example.com']);
    $folio = Folio::factory()->create(['branch_id' => $branch->id, 'guest_id' => $guest->id, 'status' => FolioStatus::Open]);
    $folio->charges()->create(['charge_type' => ChargeType::Room, 'description' => 'Room charge', 'amount_cents' => 20000, 'charge_date' => now()->toDateString()]);
    app(FolioBalanceCalculator::class)->recalculate($folio);

    $staff = User::factory()->create(['tenant_id' => $tenant->id]);
    $staff->assignRole($role);
    $branch->staff()->attach($staff->id, ['role_id' => $role->id, 'is_primary' => true]);

    return [$folio->fresh(), $staff];
}

test('the manual payment form no longer offers gateway methods', function (): void {
    [$folio, $staff] = makeFolioAndCashier();

    Livewire::actingAs($staff)
        ->test(FolioShow::class, ['folio' => $folio])
        ->set('showPaymentForm', true)
        ->assertOk()
        ->assertDontSee('value="paystack"', false)
        ->assertSee('value="cash"', false);
});

test('paying online creates a Pending payment and redirects to the checkout URL', function (): void {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.com/xyz', 'access_code' => 'xyz', 'reference' => 'ignored'],
        ], 200),
    ]);

    [$folio, $staff] = makeFolioAndCashier();

    Livewire::actingAs($staff)
        ->test(FolioShow::class, ['folio' => $folio])
        ->set('payOnlineAmount', '200.00')
        ->call('payWithPaystack')
        ->assertRedirect('https://checkout.paystack.com/xyz');

    expect(Payment::where('folio_id', $folio->id)->where('method', PaymentMethod::Paystack)->where('status', PaymentStatus::Pending)->exists())->toBeTrue();
});

test('a staff member without payments.process cannot start an online payment', function (): void {
    [$folio] = makeFolioAndCashier();

    // Can view the folio (mount()'s own authorize('view', ...) must pass so
    // we reach payWithPaystack at all) but deliberately has no payment
    // permission — a front-desk-view-only role, not a cashier.
    Permission::firstOrCreate(['name' => 'folios.view', 'guard_name' => 'web']);
    $viewOnlyRole = Role::firstOrCreate(['name' => 'Folio Test View Only', 'guard_name' => 'web']);
    $viewOnlyRole->givePermissionTo('folios.view');

    $bareStaff = User::factory()->create(['tenant_id' => $folio->branch->tenant_id, 'current_branch_id' => $folio->branch_id]);
    $bareStaff->assignRole($viewOnlyRole);
    $folio->branch->staff()->attach($bareStaff->id, ['role_id' => $viewOnlyRole->id, 'is_primary' => false]);

    Livewire::actingAs($bareStaff)
        ->test(FolioShow::class, ['folio' => $folio])
        ->set('payOnlineAmount', '200.00')
        ->call('payWithPaystack')
        ->assertForbidden();
});

test('refunding requires the payments.refund permission', function (): void {
    [$folio, $staff] = makeFolioAndCashier();
    $staff->removeRole('Folio Test Cashier');
    Permission::firstOrCreate(['name' => 'folios.view', 'guard_name' => 'web']);
    $viewOnlyRole = Role::firstOrCreate(['name' => 'Folio Test Viewer', 'guard_name' => 'web']);
    $viewOnlyRole->givePermissionTo('folios.view');
    $staff->assignRole($viewOnlyRole);

    $payment = Payment::factory()->create([
        'branch_id' => $folio->branch_id,
        'folio_id' => $folio->id,
        'method' => PaymentMethod::Paystack,
        'status' => PaymentStatus::Completed,
        'gateway_reference' => 'refund-target',
    ]);

    Livewire::actingAs($staff)
        ->test(FolioShow::class, ['folio' => $folio])
        ->call('refundPayment', $payment->id)
        ->assertForbidden();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Completed);
});

test('returning from Paystack with a reference confirms the payment on page load', function (): void {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'success', 'reference' => 'callback-reference', 'amount' => 20000, 'currency' => 'NGN'],
        ], 200),
    ]);

    [$folio, $staff] = makeFolioAndCashier();
    Payment::factory()->create([
        'branch_id' => $folio->branch_id,
        'folio_id' => $folio->id,
        'method' => PaymentMethod::Paystack,
        'status' => PaymentStatus::Pending,
        'amount_cents' => 20000,
        'gateway_reference' => 'callback-reference',
    ]);

    $response = $this->actingAs($staff)->get(route('folios.show', $folio) . '?reference=callback-reference');

    $response->assertOk();
    expect(Payment::where('gateway_reference', 'callback-reference')->first()->status)->toBe(PaymentStatus::Completed);
});
