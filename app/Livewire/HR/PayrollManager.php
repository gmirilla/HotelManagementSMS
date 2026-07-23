<?php

declare(strict_types=1);

namespace App\Livewire\HR;

use App\Domain\HR\Actions\ProcessPayrollRunAction;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payroll')]
class PayrollManager extends Component
{
    use InteractsWithActiveBranch;

    public string $periodStart = '';

    public string $periodEnd = '';

    public ?int $viewingRunId = null;

    public function mount(): void
    {
        $this->periodStart = now()->startOfMonth()->toDateString();
        $this->periodEnd = now()->endOfMonth()->toDateString();
    }

    #[Computed]
    public function payrollRuns(): Collection
    {
        return PayrollRun::where('branch_id', $this->branchId)
            ->withCount('payslips')
            ->orderByDesc('period_start')
            ->get();
    }

    #[Computed]
    public function viewingRun(): ?PayrollRun
    {
        if (! $this->viewingRunId) {
            return null;
        }

        return PayrollRun::with('payslips.employee')->find($this->viewingRunId);
    }

    public function process(ProcessPayrollRunAction $processPayrollRun): void
    {
        $this->authorize('create', PayrollRun::class);

        $this->validate([
            'periodStart' => ['required', 'date'],
            'periodEnd' => ['required', 'date', 'after_or_equal:periodStart'],
        ]);

        $run = $processPayrollRun->handle(
            $this->branchId,
            Carbon::parse($this->periodStart),
            Carbon::parse($this->periodEnd),
            auth()->user(),
        );

        $this->viewingRunId = $run->id;
        unset($this->payrollRuns);
    }

    public function view(int $runId): void
    {
        $run = PayrollRun::findOrFail($runId);
        $this->authorize('view', $run);

        $this->viewingRunId = $runId;
    }

    public function render()
    {
        return view('livewire.hr.payroll-manager');
    }
}
