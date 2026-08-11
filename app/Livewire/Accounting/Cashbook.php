<?php

declare(strict_types=1);

namespace App\Livewire\Accounting;

use App\Domain\Accounting\Enums\CashbookEntryType;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\CashbookEntry;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Cashbook')]
class Cashbook extends Component
{
    use InteractsWithActiveBranch;

    public string $shiftDate = '';

    public bool $showForm = false;

    public string $entryType = 'cash_in';

    public string $amount = '';

    public string $reason = '';

    public function mount(): void
    {
        $this->shiftDate = now()->toDateString();
    }

    #[Computed]
    public function entries(): Collection
    {
        return CashbookEntry::where('branch_id', $this->branchId)
            ->whereDate('shift_date', $this->shiftDate)
            ->with('cashier')
            ->orderBy('created_at')
            ->get();
    }

    #[Computed]
    public function runningBalanceCents(): int
    {
        return $this->entries->sum(fn (CashbookEntry $entry) => $entry->signedAmountCents());
    }

    public function create(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('accounting.manage'), 403);

        $this->reset(['amount', 'reason']);
        $this->entryType = 'cash_in';
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('accounting.manage'), 403);

        $this->validate([
            'entryType' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        CashbookEntry::create([
            'branch_id' => $this->branchId,
            'cashier_user_id' => auth()->id(),
            'entry_type' => CashbookEntryType::from($this->entryType),
            'amount_cents' => (int) round(((float) $this->amount) * 100),
            'reason' => $this->reason ?: null,
            'shift_date' => $this->shiftDate,
        ]);

        $this->showForm = false;
        unset($this->entries);
    }

    public function toggleReconciled(int $entryId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('accounting.manage'), 403);

        $entry = CashbookEntry::findOrFail($entryId);
        $entry->update(['reconciled' => ! $entry->reconciled]);
        unset($this->entries);
    }

    public function exportCsv(): StreamedResponse
    {
        $entries = $this->entries;
        $runningBalance = $this->runningBalanceCents;

        return response()->streamDownload(function () use ($entries, $runningBalance): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Time', 'Cashier', 'Type', 'Reason', 'Amount', 'Reconciled']);

            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry->created_at->format('H:i'),
                    $entry->cashier->name,
                    $entry->entry_type === CashbookEntryType::CashIn ? 'Cash in' : 'Cash out',
                    $entry->reason,
                    number_format($entry->signedAmountCents() / 100, 2, '.', ''),
                    $entry->reconciled ? 'Yes' : 'No',
                ]);
            }

            fputcsv($handle, ['', '', '', 'Running balance', number_format($runningBalance / 100, 2, '.', ''), '']);
            fclose($handle);
        }, "cashbook-{$this->shiftDate}.csv", ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.accounting.cashbook');
    }
}
