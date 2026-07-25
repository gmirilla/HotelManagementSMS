<?php

declare(strict_types=1);

use App\Domain\Settings\Actions\UpdatePaymentSettingsAction;
use App\Livewire\Admin\PaymentSettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('updating payment settings sets the public and secret keys', function (): void {
    $tenant = Tenant::factory()->create();

    app(UpdatePaymentSettingsAction::class)->handle($tenant, 'pk_test_abc', 'sk_test_xyz');

    expect($tenant->fresh()->paystack_public_key)->toBe('pk_test_abc')
        ->and($tenant->fresh()->paystack_secret_key)->toBe('sk_test_xyz');
});

test('a blank secret key leaves the previously-stored key unchanged', function (): void {
    $tenant = Tenant::factory()->create(['paystack_secret_key' => 'sk_test_original']);

    app(UpdatePaymentSettingsAction::class)->handle($tenant, 'pk_test_new', null);

    expect($tenant->fresh()->paystack_public_key)->toBe('pk_test_new')
        ->and($tenant->fresh()->paystack_secret_key)->toBe('sk_test_original');
});

test('the secret key is encrypted at rest', function (): void {
    $tenant = Tenant::factory()->create();

    app(UpdatePaymentSettingsAction::class)->handle($tenant, null, 'sk_test_super_secret');

    $rawColumnValue = DB::table('tenants')->where('id', $tenant->id)->value('paystack_secret_key');

    expect($rawColumnValue)->not->toBe('sk_test_super_secret')
        ->and($tenant->fresh()->paystack_secret_key)->toBe('sk_test_super_secret');
});

test('a settings.manage holder can update payment settings through the component', function (): void {
    Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Payment Settings Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('settings.manage');

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(PaymentSettings::class)
        ->set('publicKey', 'pk_test_new')
        ->set('secretKey', 'sk_test_new')
        ->call('save')
        ->assertHasNoErrors();

    expect($tenant->fresh()->paystack_public_key)->toBe('pk_test_new')
        ->and($tenant->fresh()->paystack_secret_key)->toBe('sk_test_new');
});

test('a user without settings.manage cannot open payment settings', function (): void {
    Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Livewire::actingAs($user)->test(PaymentSettings::class)->assertForbidden();
});

test('the webhook URL is shown for the tenant to configure in their own Paystack dashboard', function (): void {
    Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Payment Settings Manager 2', 'guard_name' => 'web']);
    $role->givePermissionTo('settings.manage');

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(PaymentSettings::class)
        ->assertSee(route('webhooks.paystack'));
});
