<?php

declare(strict_types=1);

use App\Domain\HR\Actions\ApproveLeaveRequestAction;
use App\Domain\HR\Actions\ClockInAction;
use App\Domain\HR\Actions\ClockOutAction;
use App\Domain\HR\Actions\ProcessPayrollRunAction;
use App\Domain\HR\Actions\RecordManualAttendanceAction;
use App\Domain\HR\Actions\RejectLeaveRequestAction;
use App\Domain\HR\Actions\SubmitLeaveRequestAction;
use App\Domain\HR\Enums\AttendanceStatus;
use App\Domain\HR\Enums\EmployeeStatus;
use App\Domain\HR\Enums\LeaveRequestStatus;
use App\Domain\HR\Enums\PayrollRunStatus;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

test('submitting a leave request within the remaining balance succeeds', function (): void {
    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['days_per_year' => 10]);
    LeaveBalance::factory()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => 2026, 'entitled_days' => 10]);

    $request = app(SubmitLeaveRequestAction::class)->handle($employee, $leaveType, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-03'));

    expect($request->days_requested)->toBe(3)
        ->and($request->status)->toBe(LeaveRequestStatus::Pending);
});

test('submitting a leave request that exceeds the remaining balance is rejected', function (): void {
    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['days_per_year' => 2]);
    LeaveBalance::factory()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => 2026, 'entitled_days' => 2]);

    app(SubmitLeaveRequestAction::class)->handle($employee, $leaveType, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-05'));
})->throws(ValidationException::class);

test('approving a leave request marks it approved and records the approver', function (): void {
    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['days_per_year' => 10]);
    LeaveBalance::factory()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => 2026, 'entitled_days' => 10]);
    $approver = User::factory()->create();

    $request = app(SubmitLeaveRequestAction::class)->handle($employee, $leaveType, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-02'));
    app(ApproveLeaveRequestAction::class)->handle($request, $approver);

    expect($request->fresh()->status)->toBe(LeaveRequestStatus::Approved)
        ->and($request->fresh()->approved_by_user_id)->toBe($approver->id);
});

test('an already-approved leave request cannot be approved again', function (): void {
    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['days_per_year' => 10]);
    LeaveBalance::factory()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => 2026, 'entitled_days' => 10]);
    $approver = User::factory()->create();

    $request = app(SubmitLeaveRequestAction::class)->handle($employee, $leaveType, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-02'));
    app(ApproveLeaveRequestAction::class)->handle($request, $approver);

    app(ApproveLeaveRequestAction::class)->handle($request, $approver);
})->throws(ValidationException::class);

test('rejecting a leave request requires a reason and records it', function (): void {
    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['days_per_year' => 10]);
    LeaveBalance::factory()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => 2026, 'entitled_days' => 10]);
    $rejector = User::factory()->create();

    $request = app(SubmitLeaveRequestAction::class)->handle($employee, $leaveType, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-02'));
    app(RejectLeaveRequestAction::class)->handle($request, $rejector, 'Insufficient coverage');

    expect($request->fresh()->status)->toBe(LeaveRequestStatus::Rejected)
        ->and($request->fresh()->rejection_reason)->toBe('Insufficient coverage');
});

test('clocking in twice on the same day is rejected', function (): void {
    $employee = Employee::factory()->create();

    app(ClockInAction::class)->handle($employee);
    app(ClockInAction::class)->handle($employee);
})->throws(ValidationException::class);

test('clocking out without having clocked in is rejected', function (): void {
    $employee = Employee::factory()->create();

    app(ClockOutAction::class)->handle($employee);
})->throws(ValidationException::class);

test('a full clock in / clock out cycle records both timestamps', function (): void {
    $employee = Employee::factory()->create();

    app(ClockInAction::class)->handle($employee);
    $record = app(ClockOutAction::class)->handle($employee);

    expect($record->clock_in_at)->not->toBeNull()
        ->and($record->clock_out_at)->not->toBeNull();
});

test('manual attendance recording upserts a single record per employee per day', function (): void {
    $employee = Employee::factory()->create();

    app(RecordManualAttendanceAction::class)->handle($employee, Carbon::parse('2026-07-01'), AttendanceStatus::Absent);
    app(RecordManualAttendanceAction::class)->handle($employee, Carbon::parse('2026-07-01'), AttendanceStatus::Present);

    expect(AttendanceRecord::where('employee_id', $employee->id)->count())->toBe(1)
        ->and(AttendanceRecord::where('employee_id', $employee->id)->first()->status)->toBe(AttendanceStatus::Present);
});

test('processing a payroll run generates one payslip per active employee and locks the run', function (): void {
    $branch = Branch::factory()->create();
    $employee = Employee::factory()->create(['branch_id' => $branch->id, 'status' => EmployeeStatus::Active, 'base_salary_cents' => 300000, 'hire_date' => '2026-01-01']);
    $terminatedEmployee = Employee::factory()->create(['branch_id' => $branch->id, 'status' => EmployeeStatus::Terminated, 'hire_date' => '2026-01-01']);
    $hrUser = User::factory()->create();

    $run = app(ProcessPayrollRunAction::class)->handle($branch->id, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), $hrUser);

    expect($run->status)->toBe(PayrollRunStatus::Processed)
        ->and($run->payslips()->count())->toBe(1)
        ->and($run->payslips()->first()->employee_id)->toBe($employee->id);

    expect(PayrollRun::find($run->id)->payslips()->where('employee_id', $terminatedEmployee->id)->exists())->toBeFalse();
});

test('a payroll run cannot be processed twice', function (): void {
    $branch = Branch::factory()->create();
    Employee::factory()->create(['branch_id' => $branch->id, 'status' => EmployeeStatus::Active, 'hire_date' => '2026-01-01']);
    $hrUser = User::factory()->create();

    app(ProcessPayrollRunAction::class)->handle($branch->id, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), $hrUser);
    app(ProcessPayrollRunAction::class)->handle($branch->id, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), $hrUser);
})->throws(ValidationException::class);
