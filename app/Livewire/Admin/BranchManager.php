<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Branch\Actions\CreateBranchAction;
use App\Domain\Branch\Actions\SetBranchActiveStatusAction;
use App\Domain\Branch\Actions\UpdateBranchAction;
use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Branches')]
class BranchManager extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingBranchId = null;

    public string $name = '';

    public string $code = '';

    public string $currency = '';

    public string $timezone = '';

    public string $addressLine1 = '';

    public string $city = '';

    public string $country = '';

    public string $checkInTime = '14:00';

    public string $checkOutTime = '12:00';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('branches.manage'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function branches(): LengthAwarePaginator
    {
        return Branch::where('tenant_id', auth()->user()->tenant_id)
            ->when($this->search !== '', fn ($q) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(15);
    }

    public function create(): void
    {
        $tenant = auth()->user()->tenant;

        $this->reset(['editingBranchId', 'name', 'code', 'addressLine1', 'city', 'country']);
        $this->currency = $tenant->default_currency;
        $this->timezone = $tenant->default_timezone;
        $this->checkInTime = '14:00';
        $this->checkOutTime = '12:00';
        $this->showForm = true;
    }

    public function edit(int $branchId): void
    {
        $branch = Branch::findOrFail($branchId);
        abort_unless($branch->tenant_id === auth()->user()->tenant_id, 403);

        $this->editingBranchId = $branch->id;
        $this->name = $branch->name;
        $this->code = $branch->code;
        $this->currency = $branch->currency;
        $this->timezone = $branch->timezone;
        $this->addressLine1 = $branch->address_line1 ?? '';
        $this->city = $branch->city ?? '';
        $this->country = $branch->country ?? '';
        // check_in_time/check_out_time use a `datetime:H:i:s` cast, which
        // hydrates a Carbon instance anchored at today's date (TIME columns
        // have no date of their own) — (string) casting it yields the full
        // "Y-m-d H:i:s", not just the time, so ->format() is required.
        $this->checkInTime = $branch->check_in_time->format('H:i');
        $this->checkOutTime = $branch->check_out_time->format('H:i');
        $this->showForm = true;
    }

    public function save(CreateBranchAction $createBranch, UpdateBranchAction $updateBranch): void
    {
        abort_unless(auth()->user()->hasPermissionTo('branches.manage'), 403);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($this->editingBranchId)],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'timezone' => ['required', 'string', 'timezone'],
            'addressLine1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'checkInTime' => ['required', 'date_format:H:i'],
            'checkOutTime' => ['required', 'date_format:H:i'],
        ]);

        if ($this->editingBranchId) {
            $branch = Branch::findOrFail($this->editingBranchId);
            abort_unless($branch->tenant_id === auth()->user()->tenant_id, 403);

            $updateBranch->handle(
                $branch,
                $this->name,
                strtoupper($this->code),
                strtoupper($this->currency),
                $this->timezone,
                $this->addressLine1 ?: null,
                $this->city ?: null,
                $this->country ?: null,
                $this->checkInTime,
                $this->checkOutTime,
            );
        } else {
            $createBranch->handle(
                auth()->user()->tenant,
                $this->name,
                strtoupper($this->code),
                strtoupper($this->currency),
                $this->timezone,
                $this->addressLine1 ?: null,
                $this->city ?: null,
                $this->country ?: null,
                $this->checkInTime,
                $this->checkOutTime,
            );
        }

        $this->showForm = false;
    }

    public function toggleActive(int $branchId, SetBranchActiveStatusAction $setStatus): void
    {
        abort_unless(auth()->user()->hasPermissionTo('branches.manage'), 403);

        $branch = Branch::findOrFail($branchId);
        abort_unless($branch->tenant_id === auth()->user()->tenant_id, 403);

        $setStatus->handle($branch, ! $branch->is_active);
    }

    public function render()
    {
        return view('livewire.admin.branch-manager', ['branches' => $this->branches()]);
    }
}
