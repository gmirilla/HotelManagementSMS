<?php

declare(strict_types=1);

namespace App\Domain\HR\Actions;

use App\Domain\HR\Enums\EmployeeStatus;
use App\Domain\HR\Enums\PayrollRunStatus;
use App\Domain\HR\Support\PayrollCalculator;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The sole write path for payroll runs — generates one payslip per active
 * employee in the branch from the attendance ledger (see PayrollCalculator)
 * and locks the run so it can never be silently regenerated with different
 * numbers after the fact.
 */
class ProcessPayrollRunAction
{
    public function __construct(private readonly PayrollCalculator $payrollCalculator) {}

    public function handle(int $branchId, Carbon $periodStart, Carbon $periodEnd, User $processedBy): PayrollRun
    {
        return DB::transaction(function () use ($branchId, $periodStart, $periodEnd, $processedBy) {
            // period_start/period_end are Eloquent 'date' cast columns, which
            // serialize to full "Y-m-d H:i:s" strings in the database — an
            // exact-equality lookup (as firstOrCreate() builds) against a
            // "Y-m-d" string never matches an existing row, so the match must
            // be done via whereDate() first.
            $run = PayrollRun::where('branch_id', $branchId)
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())
                ->first();

            if (! $run) {
                $run = PayrollRun::create([
                    'branch_id' => $branchId,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'status' => PayrollRunStatus::Draft,
                ]);
            }

            if ($run->status !== PayrollRunStatus::Draft) {
                throw ValidationException::withMessages(['status' => __('This payroll run has already been processed.')]);
            }

            $employees = Employee::where('branch_id', $branchId)
                ->where('status', EmployeeStatus::Active)
                ->where('hire_date', '<=', $periodEnd)
                ->get();

            foreach ($employees as $employee) {
                $figures = $this->payrollCalculator->calculateForEmployee($employee, $periodStart, $periodEnd);

                $run->payslips()->updateOrCreate(['employee_id' => $employee->id], $figures);
            }

            $run->update([
                'status' => PayrollRunStatus::Processed,
                'processed_by_user_id' => $processedBy->id,
                'processed_at' => now(),
            ]);

            return $run;
        });
    }
}
