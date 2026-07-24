<?php

declare(strict_types=1);

/**
 * NFR-SEC-001 (input validation) is enforced primarily via FormRequests,
 * but the model layer's #[Fillable(...)] allowlist is the last line of
 * defense against mass-assignment — these tests confirm sensitive columns
 * genuinely aren't settable through Model::create()/fill(), independent of
 * whatever a controller or FormRequest does upstream.
 */

use App\Livewire\CRM\CorporateAccountManager;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CorporateAccount;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('security-sensitive user columns cannot be set via mass assignment', function (): void {
    $tenant = Tenant::factory()->create();

    $user = User::create([
        'name' => 'Test User',
        'email' => 'mass-assignment-test@example.test',
        'password' => 'secret1234',
        'tenant_id' => $tenant->id,
        // None of the following are in User::class's #[Fillable(...)] list.
        'mfa_enabled' => true,
        'mfa_secret' => 'attacker-supplied-secret',
        'failed_login_attempts' => 99,
        'locked_until' => now()->addYear(),
        'email_verified_at' => now(),
    ]);

    // Asserted against a fresh reload rather than the in-memory instance:
    // mfa_enabled/failed_login_attempts have database-level defaults that
    // Eloquent doesn't back-fill onto the in-memory model after insert.
    $fresh = $user->fresh();

    expect($fresh->mfa_enabled)->toBeFalse()
        ->and($fresh->mfa_secret)->toBeNull()
        ->and($fresh->failed_login_attempts)->toBe(0)
        ->and($fresh->locked_until)->toBeNull()
        ->and($fresh->email_verified_at)->toBeNull();
});

test('an account\'s ledger-derived balance cannot be set via mass assignment', function (): void {
    $branch = Branch::factory()->create();

    $account = Account::create([
        'branch_id' => $branch->id,
        'code' => '9999',
        'name' => 'Test Account',
        'account_type' => 'asset',
        // Not a real column at all — proves unknown keys are dropped, not
        // just miscast.
        'balance_cents' => 999999999,
    ]);

    expect($account->getAttributes())->not->toHaveKey('balance_cents');
});

test('editing a corporate account through the manager never changes its tenant_id', function (): void {
    // tenant_id is legitimately Fillable on this model (needed at creation
    // by CorporateAccountManager::save()'s create branch) — model-layer
    // #[Fillable] isn't the control here. The control is that the update
    // branch's $data array never includes tenant_id, so an edit can't move
    // a record between tenants even indirectly. Cross-tenant *access* (a
    // different attack shape — editing someone else's record outright) is
    // covered separately in AuthorizationBoundaryTest.php.
    Permission::firstOrCreate(['name' => 'crm.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'MassAssignment CRM Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('crm.manage');

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole($role);

    $account = CorporateAccount::factory()->create(['tenant_id' => $tenant->id, 'company_name' => 'Original Co']);

    Livewire::actingAs($user)
        ->test(CorporateAccountManager::class)
        ->call('edit', $account->id)
        ->set('companyName', 'Renamed Co')
        ->call('save')
        ->assertOk();

    expect($account->fresh()->tenant_id)->toBe($tenant->id)
        ->and($account->fresh()->company_name)->toBe('Renamed Co');
});
