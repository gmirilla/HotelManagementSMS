<?php

declare(strict_types=1);

namespace App\Livewire\HR;

use App\Domain\HR\Actions\ClockInAction;
use App\Domain\HR\Actions\ClockOutAction;
use App\Domain\HR\Actions\RecordManualAttendanceAction;
use App\Domain\HR\Enums\AttendanceStatus;
use App\Domain\HR\Enums\EmployeeStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Attendance')]
class AttendanceBoard extends Component
{
    use InteractsWithActiveBranch;

    public string $workDate = '';

    public function mount(): void
    {
        $this->workDate = now()->toDateString();
    }

    /**
     * @return Collection<int, array{employee: Employee, record: ?AttendanceRecord}>
     */
    #[Computed]
    public function rows(): Collection
    {
        $records = AttendanceRecord::where('branch_id', $this->branchId)
            ->whereDate('work_date', $this->workDate)
            ->get()
            ->keyBy('employee_id');

        return Employee::where('branch_id', $this->branchId)
            ->where('status', EmployeeStatus::Active)
            ->orderBy('last_name')
            ->get()
            ->map(fn (Employee $employee) => [
                'employee' => $employee,
                'record' => $records->get($employee->id),
            ]);
    }

    public function clockIn(int $employeeId, ClockInAction $clockIn): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        $clockIn->handle(Employee::findOrFail($employeeId));
        unset($this->rows);
    }

    public function clockOut(int $employeeId, ClockOutAction $clockOut): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        $clockOut->handle(Employee::findOrFail($employeeId));
        unset($this->rows);
    }

    public function markStatus(int $employeeId, string $status, RecordManualAttendanceAction $recordAttendance): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        $recordAttendance->handle(Employee::findOrFail($employeeId), Carbon::parse($this->workDate), AttendanceStatus::from($status));
        unset($this->rows);
    }

    public function render()
    {
        return view('livewire.hr.attendance-board');
    }
}
