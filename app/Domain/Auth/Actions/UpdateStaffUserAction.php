<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * The sole write path for editing an existing staff account: profile
 * fields, role, branch assignments, and (optionally) a new password. Branch
 * assignments are always fully re-synced rather than diffed — the pivot
 * also carries role_id, so a role change must be reflected on every
 * existing assignment row, not just new ones.
 */
class UpdateStaffUserAction
{
    /**
     * @param  list<int>  $branchIds
     */
    public function handle(
        User $user,
        string $name,
        string $email,
        string $roleName,
        array $branchIds = [],
        ?int $primaryBranchId = null,
        ?string $newPassword = null,
    ): User {
        return DB::transaction(function () use ($user, $name, $email, $roleName, $branchIds, $primaryBranchId, $newPassword) {
            $user->update(['name' => $name, 'email' => $email]);

            if ($newPassword !== null && $newPassword !== '') {
                $user->forceFill([
                    'password' => Hash::make($newPassword),
                    'password_changed_at' => now(),
                ])->save();

                $user->passwordHistories()->create(['password' => $user->password]);
            }

            $user->syncRoles([$roleName]);

            $this->resyncBranchAssignments($user, $roleName, $branchIds, $primaryBranchId);

            return $user->fresh();
        });
    }

    /**
     * @param  list<int>  $branchIds
     */
    private function resyncBranchAssignments(User $user, string $roleName, array $branchIds, ?int $primaryBranchId): void
    {
        $user->branches()->detach();

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

        if (! in_array($user->current_branch_id, $branchIds, true)) {
            $user->update(['current_branch_id' => $primaryBranchId]);
        }
    }
}
