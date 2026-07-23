<?php

declare(strict_types=1);

use App\Domain\HR\Enums\AttendanceStatus;
use App\Domain\HR\Enums\LeaveRequestStatus;
use App\Domain\HR\Support\LeaveBalanceCalculator;
use App\Domain\HR\Support\PayrollCalculator;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Carbon;

test('leave balance calculator falls back to the leave type default when no explicit balance row exists', function (): void {
    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['days_per_year' => 15]);

    expect(app(LeaveBalanceCalculator::class)->remainingDays($employee, $leaveType, 2026))->toBe(15);
});

test('leave balance calculator subtracts only approved days used, ignoring pending and rejected requests', function (): void {
    $employee = Employee::factory()->create();
    $leaveType = LeaveType::factory()->create(['days_per_year' => 20]);
    LeaveBalance::factory()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => 2026, 'entitled_days' => 20]);

    LeaveRequest::factory()->create([
        'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
        'start_date' => '2026-03-01', 'end_date' => '2026-03-05', 'days_requested' => 5,
        'status' => LeaveRequestStatus::Approved,
    ]);
    LeaveRequest::factory()->create([
        'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
        'start_date' => '2026-04-01', 'end_date' => '2026-04-10', 'days_requested' => 10,
        'status' => LeaveRequestStatus::Pending,
    ]);
    LeaveRequest::factory()->create([
        'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
        'start_date' => '2026-05-01', 'end_date' => '2026-05-10', 'days_requested' => 10,
        'status' => LeaveRequestStatus::Rejected,
    ]);

    $calculator = app(LeaveBalanceCalculator::class);

    expect($calculator->usedDays($employee, $leaveType, 2026))->toBe(5)
        ->and($calculator->remainingDays($employee, $leaveType, 2026))->toBe(15);
});

test('payroll calculator pro-rates salary and deducts a day\'s pay per absence', function (): void {
    $employee = Employee::factory()->create(['base_salary_cents' => 300000]);

    $start = Carbon::parse('2026-07-01');
    $end = Carbon::parse('2026-07-05');

    // Jul 1-3 2026 are Wed/Thu/Fri, Jul 4-5 are Sat/Sun — 3 working days.
    AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'work_date' => '2026-07-01', 'status' => AttendanceStatus::Present]);
    AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'work_date' => '2026-07-02', 'status' => AttendanceStatus::Absent]);
    AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'work_date' => '2026-07-03', 'status' => AttendanceStatus::OnLeave]);

    $figures = app(PayrollCalculator::class)->calculateForEmployee($employee, $start, $end);

    // 300000 / 3 working days = 100000 per day; one absence deducted.
    expect($figures['days_present'])->toBe(1)
        ->and($figures['days_absent'])->toBe(1)
        ->and($figures['days_on_leave'])->toBe(1)
        ->and($figures['deductions_cents'])->toBe(100000)
        ->and($figures['net_cents'])->toBe(200000);
});
