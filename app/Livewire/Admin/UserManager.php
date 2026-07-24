<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Auth\Actions\CreateStaffUserAction;
use App\Domain\Auth\Actions\UpdateStaffUserAction;
use App\Domain\Auth\Support\PasswordPolicy;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.app')]
#[Title('Users')]
class UserManager extends Component
{
    use WithPagination;

    /**
     * Roles that grant tenant/group-wide access via User::canAccessBranch()'s
     * bypass — a user holding one of these doesn't need an explicit branch
     * assignment to do their job.
     */
    private const array GLOBAL_ROLES = ['Super Administrator', 'Hotel Owner', 'General Manager', 'Auditor'];

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $roleName = '';

    /** @var list<int> */
    public array $selectedBranchIds = [];

    public ?int $primaryBranchId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function users(): LengthAwarePaginator
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->withTrashed()
            ->with('roles', 'branches')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function roleOptions(): Collection
    {
        return Role::where('guard_name', 'web')
            ->where('name', '!=', 'Guest')
            ->when(! auth()->user()->hasRole('Super Administrator'), fn ($q) => $q->whereNotIn('name', ['Super Administrator', 'Hotel Owner']))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function branchOptions(): Collection
    {
        return Branch::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->authorize('create', User::class);

        $this->reset(['editingUserId', 'name', 'email', 'password', 'password_confirmation', 'roleName', 'selectedBranchIds', 'primaryBranchId']);
        $this->showForm = true;
    }

    public function edit(int $userId): void
    {
        $target = User::with('roles', 'branches')->findOrFail($userId);
        $this->authorize('update', $target);

        $this->editingUserId = $target->id;
        $this->name = $target->name;
        $this->email = $target->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->roleName = $target->roles->first()?->name ?? '';
        $this->selectedBranchIds = $target->branches->pluck('id')->all();
        $this->primaryBranchId = $target->branches->firstWhere('pivot.is_primary', true)?->id;
        $this->showForm = true;
    }

    public function save(CreateStaffUserAction $createStaffUser, UpdateStaffUserAction $updateStaffUser): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'roleName' => ['required', 'string'],
            'selectedBranchIds' => ['array'],
            'selectedBranchIds.*' => ['integer'],
            'primaryBranchId' => ['nullable', 'integer'],
        ];

        if (! $this->editingUserId || $this->password !== '') {
            $rules['password'] = PasswordPolicy::rules();
        }

        $this->validate($rules);

        if (! in_array($this->roleName, self::GLOBAL_ROLES, true) && $this->selectedBranchIds === []) {
            $this->addError('selectedBranchIds', __('Select at least one branch for this role.'));

            return;
        }

        // Defense against a tampered branch list: every selected branch must
        // actually belong to this admin's tenant.
        $allowedBranchIds = $this->branchOptions->pluck('id')->all();
        abort_if(array_diff($this->selectedBranchIds, $allowedBranchIds) !== [], 403);

        $this->authorize('assignRole', [User::class, $this->roleName]);

        if ($this->editingUserId) {
            $target = User::findOrFail($this->editingUserId);
            $this->authorize('update', $target);

            $updateStaffUser->handle(
                $target,
                $this->name,
                $this->email,
                $this->roleName,
                $this->selectedBranchIds,
                $this->primaryBranchId,
                $this->password ?: null,
            );
        } else {
            $this->authorize('create', User::class);

            $createStaffUser->handle(
                auth()->user()->tenant,
                $this->name,
                $this->email,
                $this->password,
                $this->roleName,
                $this->selectedBranchIds,
                $this->primaryBranchId,
            );
        }

        $this->showForm = false;
    }

    public function deactivate(int $userId): void
    {
        $target = User::findOrFail($userId);
        $this->authorize('delete', $target);

        $target->delete();
    }

    public function reactivate(int $userId): void
    {
        $target = User::withTrashed()->findOrFail($userId);
        $this->authorize('restore', $target);

        $target->restore();
    }

    public function render()
    {
        return view('livewire.admin.user-manager', ['users' => $this->users()]);
    }
}
