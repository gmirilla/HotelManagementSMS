<?php

declare(strict_types=1);

namespace App\Livewire\Accounting;

use App\Domain\Accounting\Actions\RecordApPaymentAction;
use App\Domain\Accounting\Actions\RecordArPaymentAction;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\ApEntry;
use App\Models\ArEntry;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Receivables & Payables')]
class ArApManager extends Component
{
    use InteractsWithActiveBranch;

    public string $tab = 'receivables';

    public ?int $payingArId = null;

    public ?int $payingApId = null;

    public string $paymentAmount = '';

    #[Computed]
    public function receivables(): Collection
    {
        return ArEntry::where('branch_id', $this->branchId)
            ->with('corporateAccount')
            ->orderBy('due_date')
            ->get();
    }

    #[Computed]
    public function payables(): Collection
    {
        return ApEntry::where('branch_id', $this->branchId)
            ->with('supplier')
            ->orderBy('due_date')
            ->get();
    }

    public function startArPayment(int $arId): void
    {
        $this->payingArId = $arId;
        $this->paymentAmount = '';
    }

    public function recordArPayment(RecordArPaymentAction $recordPayment): void
    {
        $entry = ArEntry::findOrFail($this->payingArId);
        abort_unless(auth()->user()->canAccessBranch($entry->branch_id) && auth()->user()->hasPermissionTo('accounting.manage'), 403);

        $this->validate(['paymentAmount' => ['required', 'numeric', 'min:0.01']]);

        $recordPayment->handle($entry, (int) round(((float) $this->paymentAmount) * 100), auth()->user());

        $this->payingArId = null;
        unset($this->receivables);
    }

    public function startApPayment(int $apId): void
    {
        $this->payingApId = $apId;
        $this->paymentAmount = '';
    }

    public function recordApPayment(RecordApPaymentAction $recordPayment): void
    {
        $entry = ApEntry::findOrFail($this->payingApId);
        abort_unless(auth()->user()->canAccessBranch($entry->branch_id) && auth()->user()->hasPermissionTo('accounting.manage'), 403);

        $this->validate(['paymentAmount' => ['required', 'numeric', 'min:0.01']]);

        $recordPayment->handle($entry, (int) round(((float) $this->paymentAmount) * 100), auth()->user());

        $this->payingApId = null;
        unset($this->payables);
    }

    public function exportCsv(): StreamedResponse
    {
        if ($this->tab === 'receivables') {
            $rows = [['Company', 'Due Date', 'Status', 'Amount', 'Paid', 'Outstanding']];
            foreach ($this->receivables as $ar) {
                $rows[] = [
                    $ar->corporateAccount->company_name,
                    $ar->due_date->format('Y-m-d'),
                    ucfirst(str_replace('_', ' ', $ar->status->value)),
                    number_format($ar->amount_cents / 100, 2, '.', ''),
                    number_format($ar->paid_cents / 100, 2, '.', ''),
                    number_format($ar->outstandingCents() / 100, 2, '.', ''),
                ];
            }
            $filename = 'receivables-' . now()->toDateString() . '.csv';
        } else {
            $rows = [['Supplier', 'Due Date', 'Status', 'Amount', 'Paid', 'Outstanding']];
            foreach ($this->payables as $ap) {
                $rows[] = [
                    $ap->supplier->name,
                    $ap->due_date->format('Y-m-d'),
                    ucfirst(str_replace('_', ' ', $ap->status->value)),
                    number_format($ap->amount_cents / 100, 2, '.', ''),
                    number_format($ap->paid_cents / 100, 2, '.', ''),
                    number_format($ap->outstandingCents() / 100, 2, '.', ''),
                ];
            }
            $filename = 'payables-' . now()->toDateString() . '.csv';
        }

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.accounting.ar-ap-manager');
    }
}
