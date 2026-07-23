<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PayslipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable([
    'payroll_run_id', 'employee_id', 'basic_cents', 'allowances_cents', 'deductions_cents',
    'gross_cents', 'net_cents', 'days_present', 'days_absent', 'days_on_leave',
])]
class Payslip extends Model
{
    /** @use HasFactory<PayslipFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'basic_cents' => 'integer',
            'allowances_cents' => 'integer',
            'deductions_cents' => 'integer',
            'gross_cents' => 'integer',
            'net_cents' => 'integer',
            'days_present' => 'integer',
            'days_absent' => 'integer',
            'days_on_leave' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PayrollRun, $this>
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
