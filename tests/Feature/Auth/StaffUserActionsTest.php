<?php

declare(strict_types=1);

use App\Domain\Auth\Actions\CreateStaffUserAction;
use App\Domain\Auth\Actions\UpdateStaffUserAction;
use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'housekeeping.manage', 'guard_name' => 'web']);
    $this->branchRole = Role::firstOrCreate(['name' => 'Housekeeping Supervisor', 'guard_name' => 'web']);
    $this->branchRole->givePermissionTo('housekeeping.manage');

    Role::firstOrCreate(['name' => 'General Manager', 'guard_name' => 'web']);

    $this->tenant = Tenant::factory()->create();
    $this->branchA = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->branchB = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
});

test('creating a staff user assigns the role, branches, and a primary branch', function (): void {
    $user = app(CreateStaffUserAction::class)->handle(
        $this->tenant,
        'Nadia Housekeeper',
        'nadia@aurorahotels.test',
        'Correct-Horse-42!',
        'Housekeeping Supervisor',
        [$this->branchA->id, $this->branchB->id],
        $this->branchB->id,
    );

    expect($user->tenant_id)->toBe($this->tenant->id)
        ->and($user->hasRole('Housekeeping Supervisor'))->toBeTrue()
        ->and($user->branches()->pluck('branches.id')->all())->toEqualCanonicalizing([$this->branchA->id, $this->branchB->id])
        ->and($user->branches()->wherePivot('is_primary', true)->first()?->id)->toBe($this->branchB->id)
        ->and($user->current_branch_id)->toBe($this->branchB->id)
        ->and(Hash::check('Correct-Horse-42!', $user->password))->toBeTrue()
        ->and($user->passwordHistories()->count())->toBe(1);
});

test('creating a staff user with a tenant-wide role needs no branch assignment', function (): void {
    $user = app(CreateStaffUserAction::class)->handle(
        $this->tenant,
        'Gwen GM',
        'gwen@aurorahotels.test',
        'Correct-Horse-42!',
        'General Manager',
    );

    expect($user->hasRole('General Manager'))->toBeTrue()
        ->and($user->branches()->count())->toBe(0)
        ->and($user->current_branch_id)->toBeNull();
});

test('updating a staff user re-syncs role and branch assignments', function (): void {
    $user = app(CreateStaffUserAction::class)->handle(
        $this->tenant,
        'Nadia Housekeeper',
        'nadia@aurorahotels.test',
        'Correct-Horse-42!',
        'Housekeeping Supervisor',
        [$this->branchA->id],
        $this->branchA->id,
    );

    app(UpdateStaffUserAction::class)->handle(
        $user,
        'Nadia Supervisor',
        'nadia@aurorahotels.test',
        'Housekeeping Supervisor',
        [$this->branchB->id],
        $this->branchB->id,
    );

    $user = $user->fresh(['branches']);

    expect($user->name)->toBe('Nadia Supervisor')
        ->and($user->branches->pluck('id')->all())->toBe([$this->branchB->id])
        ->and($user->current_branch_id)->toBe($this->branchB->id);
});

test('updating a staff user only changes the password when a new one is supplied', function (): void {
    $user = app(CreateStaffUserAction::class)->handle(
        $this->tenant,
        'Nadia Housekeeper',
        'nadia@aurorahotels.test',
        'Correct-Horse-42!',
        'Housekeeping Supervisor',
        [$this->branchA->id],
    );
    $originalHash = $user->password;

    app(UpdateStaffUserAction::class)->handle(
        $user,
        $user->name,
        $user->email,
        'Housekeeping Supervisor',
        [$this->branchA->id],
    );

    expect($user->fresh()->password)->toBe($originalHash)
        ->and($user->passwordHistories()->count())->toBe(1);

    app(UpdateStaffUserAction::class)->handle(
        $user,
        $user->name,
        $user->email,
        'Housekeeping Supervisor',
        [$this->branchA->id],
        null,
        'Another-Correct-Horse-99!',
    );

    $user = $user->fresh();

    expect($user->password)->not->toBe($originalHash)
        ->and(Hash::check('Another-Correct-Horse-99!', $user->password))->toBeTrue()
        ->and($user->passwordHistories()->count())->toBe(2);
});
