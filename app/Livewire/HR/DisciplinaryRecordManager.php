<?php

declare(strict_types=1);

namespace App\Livewire\HR;

use App\Domain\HR\Enums\DisciplinarySeverity;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\DisciplinaryRecord;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Disciplinary Records')]
class DisciplinaryRecordManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $employeeId = '';

    public string $incidentDate = '';

    public string $severity = 'verbal_warning';

    public string $description = '';

    public string $actionTaken = '';

    #[Computed]
    public function isHr(): bool
    {
        return auth()->user()->hasPermissionTo('hr.manage');
    }

    #[Computed]
    public function employees(): Collection
    {
        return Employee::where('branch_id', $this->branchId)->orderBy('last_name')->get();
    }

    #[Computed]
    public function records(): Collection
    {
        $myEmployeeId = auth()->user()->employee?->id;

        return DisciplinaryRecord::whereHas('employee', fn ($q) => $q->where('branch_id', $this->branchId))
            ->when(! $this->isHr, fn ($q) => $q->where('employee_id', $myEmployeeId ?? 0))
            ->with(['employee', 'reportedBy'])
            ->orderByDesc('incident_date')
            ->get();
    }

    public function create(): void
    {
        $this->authorize('create', DisciplinaryRecord::class);

        $this->reset(['employeeId', 'description', 'actionTaken']);
        $this->incidentDate = now()->toDateString();
        $this->severity = 'verbal_warning';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', DisciplinaryRecord::class);

        $this->validate([
            'employeeId' => ['required', 'integer'],
            'incidentDate' => ['required', 'date'],
            'severity' => ['required', 'string'],
            'description' => ['required', 'string', 'max:2000'],
            'actionTaken' => ['nullable', 'string', 'max:2000'],
        ]);

        DisciplinaryRecord::create([
            'employee_id' => $this->employeeId,
            'reported_by_user_id' => auth()->id(),
            'incident_date' => $this->incidentDate,
            'severity' => $this->severity,
            'description' => $this->description,
            'action_taken' => $this->actionTaken ?: null,
        ]);

        $this->showForm = false;
        unset($this->records);
    }

    public function render()
    {
        return view('livewire.hr.disciplinary-record-manager', ['severities' => DisciplinarySeverity::cases()]);
    }
}
