<?php

declare(strict_types=1);

namespace App\Livewire\Accounting;

use App\Domain\Accounting\Support\BalanceSheetCalculator;
use App\Domain\Accounting\Support\ProfitAndLossCalculator;
use App\Domain\Accounting\Support\TrialBalanceCalculator;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function exportCsv(): StreamedResponse
    {
        $this->authorizeView();

        $rows = match ($this->tab) {
            'trial_balance' => $this->trialBalanceCsvRows(),
            'profit_loss' => $this->profitAndLossCsvRows(),
            'balance_sheet' => $this->balanceSheetCsvRows(),
            default => abort(404),
        };

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $this->exportFilename('csv'), ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(): StreamedResponse
    {
        $this->authorizeView();

        $pdf = Pdf::loadView('pdf.financial-report', [
            'tab' => $this->tab,
            'branch' => $this->activeBranch,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'asOfDate' => $this->asOfDate,
            'trialBalance' => $this->tab === 'trial_balance' ? $this->trialBalance : null,
            'trialBalanceTotals' => $this->tab === 'trial_balance' ? $this->trialBalanceTotals : null,
            'profitAndLoss' => $this->tab === 'profit_loss' ? $this->profitAndLoss : null,
            'balanceSheet' => $this->tab === 'balance_sheet' ? $this->balanceSheet : null,
        ]);

        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf->output();
            },
            $this->exportFilename('pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function exportFilename(string $extension): string
    {
        $period = $this->tab === 'balance_sheet'
            ? $this->asOfDate
            : "{$this->startDate}_to_{$this->endDate}";

        return "{$this->tab}-{$period}.{$extension}";
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function trialBalanceCsvRows(): array
    {
        $rows = [['Account Code', 'Account Name', 'Debit', 'Credit']];

        foreach ($this->trialBalance as $row) {
            $rows[] = [
                $row['account']->code,
                $row['account']->name,
                $row['debit_cents'] > 0 ? number_format($row['debit_cents'] / 100, 2, '.', '') : '',
                $row['credit_cents'] > 0 ? number_format($row['credit_cents'] / 100, 2, '.', '') : '',
            ];
        }

        $rows[] = [
            'Total',
            '',
            number_format($this->trialBalanceTotals['debit'] / 100, 2, '.', ''),
            number_format($this->trialBalanceTotals['credit'] / 100, 2, '.', ''),
        ];

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function profitAndLossCsvRows(): array
    {
        $pnl = $this->profitAndLoss;
        $rows = [['Section', 'Account', 'Amount']];

        foreach ($pnl['revenue'] as $row) {
            $rows[] = ['Revenue', $row['account']->name, number_format($row['amount_cents'] / 100, 2, '.', '')];
        }
        $rows[] = ['Revenue', 'Total revenue', number_format($pnl['total_revenue_cents'] / 100, 2, '.', '')];

        foreach ($pnl['expenses'] as $row) {
            $rows[] = ['Expenses', $row['account']->name, number_format($row['amount_cents'] / 100, 2, '.', '')];
        }
        $rows[] = ['Expenses', 'Total expenses', number_format($pnl['total_expense_cents'] / 100, 2, '.', '')];

        $rows[] = ['', 'Net income', number_format($pnl['net_income_cents'] / 100, 2, '.', '')];

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function balanceSheetCsvRows(): array
    {
        $bs = $this->balanceSheet;
        $rows = [['Section', 'Account', 'Amount']];

        foreach ($bs['assets'] as $row) {
            $rows[] = ['Assets', $row['account']->name, number_format($row['amount_cents'] / 100, 2, '.', '')];
        }
        $rows[] = ['Assets', 'Total assets', number_format($bs['total_assets_cents'] / 100, 2, '.', '')];

        foreach ($bs['liabilities'] as $row) {
            $rows[] = ['Liabilities', $row['account']->name, number_format($row['amount_cents'] / 100, 2, '.', '')];
        }
        $rows[] = ['Liabilities', 'Total liabilities', number_format($bs['total_liabilities_cents'] / 100, 2, '.', '')];

        foreach ($bs['equity'] as $row) {
            $rows[] = ['Equity', $row['account']->name, number_format($row['amount_cents'] / 100, 2, '.', '')];
        }
        $rows[] = ['Equity', 'Total equity', number_format($bs['total_equity_cents'] / 100, 2, '.', '')];

        return $rows;
    }

    public function render()
    {
        return view('livewire.accounting.financial-reports');
    }
}
