<?php

declare(strict_types=1);

namespace App\Livewire\Accounting;

use App\Domain\Accounting\Support\BalanceSheetCalculator;
use App\Domain\Accounting\Support\ProfitAndLossCalculator;
use App\Domain\Accounting\Support\TrialBalanceCalculator;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Financial Reports')]
class FinancialReports extends Component
{
    use InteractsWithActiveBranch;

    public string $tab = 'trial_balance';

    public string $startDate = '';

    public string $endDate = '';

    public string $asOfDate = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->asOfDate = now()->toDateString();
    }

    public function authorizeView(): void
    {
        abort_unless(auth()->user()->hasAnyPermission(['accounting.manage', 'accounting.view']), 403);
    }

    #[Computed]
    public function trialBalance()
    {
        $this->authorizeView();

        return app(TrialBalanceCalculator::class)->forBranch(
            $this->branchId,
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
        );
    }

    #[Computed]
    public function profitAndLoss(): array
    {
        $this->authorizeView();

        return app(ProfitAndLossCalculator::class)->forBranch(
            $this->branchId,
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
        );
    }

    #[Computed]
    public function balanceSheet(): array
    {
        $this->authorizeView();

        return app(BalanceSheetCalculator::class)->forBranch($this->branchId, Carbon::parse($this->asOfDate));
    }

    #[Computed]
    public function trialBalanceTotals(): array
    {
        return [
            'debit' => $this->trialBalance->sum('debit_cents'),
            'credit' => $this->trialBalance->sum('credit_cents'),
        ];
    }

    public function render()
    {
        return view('livewire.accounting.financial-reports');
    }
}
