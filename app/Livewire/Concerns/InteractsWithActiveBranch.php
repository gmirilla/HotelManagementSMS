<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

/**
 * Shared branch-context handling for operational Livewire components (Room,
 * Reservation, Front Desk). Defaults to the signed-in staff member's
 * `current_branch_id`; users with a group-level role (who pass every branch
 * in canAccessBranch()) get a switcher populated from every branch in their
 * tenant instead of just their assignments.
 */
trait InteractsWithActiveBranch
{
    public ?int $branchId = null;

    public function mountInteractsWithActiveBranch(): void
    {
        $user = auth()->user();

        $this->branchId = $user->current_branch_id ?? $this->accessibleBranches()->first()?->id;
    }

    #[Computed]
    public function accessibleBranches(): Collection
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['Super Administrator', 'Hotel Owner', 'General Manager', 'Auditor'])) {
            return Branch::where('tenant_id', $user->tenant_id)->where('is_active', true)->orderBy('name')->get();
        }

        return $user->branches()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function activeBranch(): ?Branch
    {
        if (! $this->branchId) {
            return null;
        }

        return $this->accessibleBranches->firstWhere('id', $this->branchId);
    }

    public function updatedBranchId(): void
    {
        abort_unless(auth()->user()->canAccessBranch((int) $this->branchId), 403);
    }
}
