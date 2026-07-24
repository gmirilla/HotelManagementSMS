<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * The sole write path for creating a staff account (as opposed to
 * RegisterGuestAction, which is the public self-registration flow for the
 * "Guest" portal role). Always tenant-scoped to the administrator doing the
 * creating — see UserPolicy for why that matters.
 */
class CreateStaffUserAction
{
    /**
     * @param  list<int>  $branchIds  Branches to assign the user to, all with $roleName. May be
     *                                empty for tenant/group-wide roles (Super Administrator, Hotel
     *                                Owner, General Manager, Auditor) that don't need a specific
     *                                branch to pass User::canAccessBranch().
     */
    public function handle(
        Tenant $tenant,
        string $name,
        string $email,
        string $password,
        string $roleName,
        array $branchIds = [],
        ?int $primaryBranchId = null,
    ): User {
        return DB::transaction(function () use ($tenant, $name, $email, $password, $roleName, $branchIds, $primaryBranchId) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
                'password_changed_at' => now(),
            ]);

            $user->assignRole($roleName);
            $user->passwordHistories()->create(['password' => $user->password]);

            $this->syncBranchAssignments($user, $roleName, $branchIds, $primaryBranchId);

            return $user;
        });
    }

    /**
     * @param  list<int>  $branchIds
     */
    private function syncBranchAssignments(User $user, string $roleName, array $branchIds, ?int $primaryBranchId): void
    {
        if ($branchIds === []) {
            return;
        }

        $role = Role::where('name', $roleName)->firstOrFail();
        $primaryBranchId ??= $branchIds[0];

        $branches = Branch::whereIn('id', $branchIds)->get();

        foreach ($branches as $branch) {
            $branch->staff()->attach($user->id, [
                'role_id' => $role->id,
                'is_primary' => $branch->id === $primaryBranchId,
            ]);
        }

        if ($user->current_branch_id === null) {
            $user->update(['current_branch_id' => $primaryBranchId]);
        }
    }
}
