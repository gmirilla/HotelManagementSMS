<?php

declare(strict_types=1);

namespace App\Livewire\Accounting;

use App\Domain\Accounting\Enums\TaxAppliesTo;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\TaxRule;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tax Rules')]
class TaxRuleManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $name = '';

    public string $ratePercent = '';

    public string $appliesTo = 'all';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ratePercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'appliesTo' => ['required', 'string'],
        ];
    }

    #[Computed]
    public function taxRules(): Collection
    {
        return TaxRule::where('branch_id', $this->branchId)->orderBy('name')->get();
    }

    public function create(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('accounting.manage'), 403);

        $this->reset(['name', 'ratePercent']);
        $this->appliesTo = 'all';
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('accounting.manage'), 403);
        $this->validate();

        TaxRule::create([
            'branch_id' => $this->branchId,
            'name' => $this->name,
            'rate_percent' => $this->ratePercent,
            'applies_to' => $this->appliesTo,
        ]);

        $this->showForm = false;
        unset($this->taxRules);
    }

    public function toggleActive(int $taxRuleId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('accounting.manage'), 403);

        $rule = TaxRule::findOrFail($taxRuleId);
        $rule->update(['is_active' => ! $rule->is_active]);
        unset($this->taxRules);
    }

    public function render()
    {
        return view('livewire.accounting.tax-rule-manager', ['applyOptions' => TaxAppliesTo::cases()]);
    }
}
