<?php

declare(strict_types=1);

namespace App\Livewire\CRM;

use App\Domain\CRM\Enums\CorporateAccountType;
use App\Models\CorporateAccount;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Corporate Accounts')]
class CorporateAccountManager extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $companyName = '';

    public string $accountType = 'corporate';

    public string $billingEmail = '';

    public string $negotiatedRate = '';

    public string $commissionPercent = '';

    public bool $directBillingEnabled = false;

    protected function rules(): array
    {
        return [
            'companyName' => ['required', 'string', 'max:255'],
            'accountType' => ['required', 'string'],
            'billingEmail' => ['nullable', 'email', 'max:255'],
            'negotiatedRate' => ['nullable', 'numeric', 'min:0'],
            'commissionPercent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    #[Computed]
    public function accounts(): Collection
    {
        return CorporateAccount::where('tenant_id', auth()->user()->tenant_id)->orderBy('company_name')->get();
    }

    public function create(): void
    {
        $this->authorize('create', CorporateAccount::class);

        $this->reset(['companyName', 'billingEmail', 'negotiatedRate', 'commissionPercent', 'editingId']);
        $this->accountType = 'corporate';
        $this->directBillingEnabled = false;
        $this->showForm = true;
    }

    public function edit(int $accountId): void
    {
        $account = CorporateAccount::findOrFail($accountId);
        $this->authorize('update', $account);

        $this->editingId = $account->id;
        $this->companyName = $account->company_name;
        $this->accountType = $account->account_type->value;
        $this->billingEmail = (string) $account->billing_email;
        $this->negotiatedRate = $account->negotiated_rate_cents ? number_format($account->negotiated_rate_cents / 100, 2, '.', '') : '';
        $this->commissionPercent = (string) ($account->commission_percent ?? '');
        $this->directBillingEnabled = $account->direct_billing_enabled;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'company_name' => $this->companyName,
            'account_type' => $this->accountType,
            'billing_email' => $this->billingEmail ?: null,
            'negotiated_rate_cents' => $this->negotiatedRate !== '' ? (int) round(((float) $this->negotiatedRate) * 100) : null,
            'commission_percent' => $this->commissionPercent !== '' ? $this->commissionPercent : null,
            'direct_billing_enabled' => $this->directBillingEnabled,
        ];

        if ($this->editingId) {
            $account = CorporateAccount::findOrFail($this->editingId);
            $this->authorize('update', $account);
            $account->update($data);
        } else {
            $this->authorize('create', CorporateAccount::class);
            $data['tenant_id'] = auth()->user()->tenant_id;
            CorporateAccount::create($data);
        }

        $this->showForm = false;
        unset($this->accounts);
    }

    public function render()
    {
        return view('livewire.crm.corporate-account-manager', ['accountTypes' => CorporateAccountType::cases()]);
    }
}
