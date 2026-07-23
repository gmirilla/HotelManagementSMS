<?php

declare(strict_types=1);

namespace App\Livewire\Accounting;

use App\Domain\Accounting\Enums\AccountType;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Chart of Accounts')]
class ChartOfAccounts extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $code = '';

    public string $name = '';

    public string $accountType = 'asset';

    public ?int $parentAccountId = null;

    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'accountType' => ['required', 'string'],
            'parentAccountId' => ['nullable', 'integer', 'exists:accounts,id'],
        ];
    }

    #[Computed]
    public function accounts(): Collection
    {
        return Account::where('branch_id', $this->branchId)
            ->orderBy('code')
            ->get();
    }

    public function create(): void
    {
        $this->authorize('create', Account::class);

        $this->reset(['code', 'name', 'parentAccountId']);
        $this->accountType = 'asset';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', Account::class);
        $this->validate();

        Account::create([
            'branch_id' => $this->branchId,
            'code' => $this->code,
            'name' => $this->name,
            'account_type' => $this->accountType,
            'parent_account_id' => $this->parentAccountId,
        ]);

        $this->showForm = false;
        unset($this->accounts);
    }

    public function render()
    {
        return view('livewire.accounting.chart-of-accounts', ['accountTypes' => AccountType::cases()]);
    }
}
