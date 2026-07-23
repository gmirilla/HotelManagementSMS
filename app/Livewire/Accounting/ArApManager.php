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
        abort_unless(auth()->user()->hasPermissionTo('accounting.manage'), 403);

        $this->validate(['paymentAmount' => ['required', 'numeric', 'min:0.01']]);

        $entry = ArEntry::findOrFail($this->payingArId);
        $recordPayment->handle($entry, (int) round(((float) $this->paymentAmount) * 100));

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
        abort_unless(auth()->user()->hasPermissionTo('accounting.manage'), 403);

        $this->validate(['paymentAmount' => ['required', 'numeric', 'min:0.01']]);

        $entry = ApEntry::findOrFail($this->payingApId);
        $recordPayment->handle($entry, (int) round(((float) $this->paymentAmount) * 100));

        $this->payingApId = null;
        unset($this->payables);
    }

    public function render()
    {
        return view('livewire.accounting.ar-ap-manager');
    }
}
