<?php

declare(strict_types=1);

namespace App\Livewire\HR;

use App\Domain\HR\Actions\ApproveLeaveRequestAction;
use App\Domain\HR\Actions\RejectLeaveRequestAction;
use App\Domain\HR\Actions\SubmitLeaveRequestAction;
use App\Domain\HR\Support\LeaveBalanceCalculator;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Leave')]
class LeaveManager extends Component
{
    use InteractsWithActiveBranch;

    public string $tab = 'requests';

    public bool $showForm = false;

    public string $employeeId = '';

    public string $leaveTypeId = '';

    public string $startDate = '';

    public string $endDate = '';

    public string $reason = '';

    public ?int $rejectingRequestId = null;

    public string $rejectionReason = '';

    public bool $showTypeForm = false;

    public string $typeName = '';

    public string $typeDaysPerYear = '';

    public bool $typeIsPaid = true;

    #[Computed]
    public function isHr(): bool
    {
        return auth()->user()->hasPermissionTo('hr.manage');
    }

    #[Computed]
    public function myEmployee(): ?Employee
    {
        return auth()->user()->employee;
    }

    #[Computed]
    public function employees(): Collection
    {
        return Employee::where('branch_id', $this->branchId)->orderBy('last_name')->get();
    }

    #[Computed]
    public function leaveTypes(): Collection
    {
        return LeaveType::where('branch_id', $this->branchId)->orderBy('name')->get();
    }

    #[Computed]
    public function leaveRequests(): Collection
    {
        return LeaveRequest::whereHas('employee', fn ($q) => $q->where('branch_id', $this->branchId))
            ->when(! $this->isHr, fn ($q) => $q->where('employee_id', $this->myEmployee?->id ?? 0))
            ->with(['employee', 'leaveType'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function openRequestForm(): void
    {
        $this->authorize('create', LeaveRequest::class);

        $this->reset(['startDate', 'endDate', 'reason', 'leaveTypeId']);
        $this->employeeId = $this->isHr ? '' : (string) $this->myEmployee?->id;
        $this->startDate = now()->toDateString();
        $this->endDate = now()->toDateString();
        $this->showForm = true;
    }

    public function submitRequest(SubmitLeaveRequestAction $submitLeaveRequest): void
    {
        $this->authorize('create', LeaveRequest::class);

        $this->validate([
            'employeeId' => ['required', 'integer'],
            'leaveTypeId' => ['required', 'integer'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $this->isHr && (int) $this->employeeId !== $this->myEmployee?->id) {
            abort(403);
        }

        $employee = Employee::findOrFail($this->employeeId);
        $leaveType = LeaveType::findOrFail($this->leaveTypeId);

        $submitLeaveRequest->handle($employee, $leaveType, Carbon::parse($this->startDate), Carbon::parse($this->endDate), $this->reason ?: null);

        $this->showForm = false;
        unset($this->leaveRequests);
    }

    public function approve(int $leaveRequestId, ApproveLeaveRequestAction $approveLeaveRequest): void
    {
        $this->authorize('review', LeaveRequest::class);

        $approveLeaveRequest->handle(LeaveRequest::findOrFail($leaveRequestId), auth()->user());
        unset($this->leaveRequests);
    }

    public function startReject(int $leaveRequestId): void
    {
        $this->authorize('review', LeaveRequest::class);

        $this->rejectingRequestId = $leaveRequestId;
        $this->rejectionReason = '';
    }

    public function reject(RejectLeaveRequestAction $rejectLeaveRequest): void
    {
        $this->authorize('review', LeaveRequest::class);

        $this->validate(['rejectionReason' => ['required', 'string', 'max:500']]);

        $rejectLeaveRequest->handle(LeaveRequest::findOrFail($this->rejectingRequestId), auth()->user(), $this->rejectionReason);

        $this->rejectingRequestId = null;
        unset($this->leaveRequests);
    }

    public function remainingDays(Employee $employee, LeaveType $leaveType): int
    {
        return app(LeaveBalanceCalculator::class)->remainingDays($employee, $leaveType, (int) now()->format('Y'));
    }

    public function createType(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        $this->reset(['typeName', 'typeDaysPerYear']);
        $this->typeIsPaid = true;
        $this->showTypeForm = true;
    }

    public function saveType(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        $this->validate([
            'typeName' => ['required', 'string', 'max:255'],
            'typeDaysPerYear' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        LeaveType::create([
            'branch_id' => $this->branchId,
            'name' => $this->typeName,
            'days_per_year' => $this->typeDaysPerYear,
            'is_paid' => $this->typeIsPaid,
        ]);

        $this->showTypeForm = false;
        unset($this->leaveTypes);
    }

    public function render()
    {
        return view('livewire.hr.leave-manager');
    }
}
