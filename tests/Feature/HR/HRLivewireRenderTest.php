<?php

declare(strict_types=1);

/**
 * Full-page Livewire render tests for every HR component, in both empty and
 * populated states. This practice exists because Action/Calculator unit
 * tests never would have caught the class of bug found in the Restaurant/
 * Inventory modules (a #[Computed] method returning the wrong Collection
 * type, which only blows up on an actual render) — see
 * RestaurantLivewireRenderTest.php / InventoryLivewireRenderTest.php for the
 * original incident this practice comes from.
 */

use App\Domain\HR\Actions\ClockInAction;
use App\Domain\HR\Actions\ProcessPayrollRunAction;
use App\Domain\HR\Actions\SubmitLeaveRequestAction;
use App\Domain\HR\Enums\DisciplinarySeverity;
use App\Domain\HR\Enums\EmployeeStatus;
use App\Domain\HR\Enums\PerformanceRating;
use App\Livewire\HR\AttendanceBoard;
use App\Livewire\HR\DisciplinaryRecordManager;
use App\Livewire\HR\EmployeeManager;
use App\Livewire\HR\LeaveManager;
use App\Livewire\HR\PayrollManager;
use App\Livewire\HR\PerformanceReviewManager;
use App\Livewire\HR\RecruitmentBoard;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\DisciplinaryRecord;
use App\Models\Employee;
use App\Models\JobOpening;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\PerformanceReview;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'hr.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']);
    $role->givePermissionTo('hr.manage');

    $this->branch = Branch::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('employee manager renders with no employees yet', function (): void {
    Livewire::actingAs($this->staff)->test(EmployeeManager::class)->assertOk();
});

test('employee manager renders with employees present', function (): void {
    Employee::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->staff)->test(EmployeeManager::class)->assertOk();
});

test('attendance board renders with no employees yet', function (): void {
    Livewire::actingAs($this->staff)->test(AttendanceBoard::class)->assertOk();
});

test('attendance board renders and reflects a clock-in recorded today', function (): void {
    $employee = Employee::factory()->create(['branch_id' => $this->branch->id, 'status' => EmployeeStatus::Active]);
    app(ClockInAction::class)->handle($employee);

    $component = Livewire::actingAs($this->staff)->test(AttendanceBoard::class)->assertOk();

    // Regression guard: work_date is an Eloquent 'date' cast column, which
    // serializes to a full "Y-m-d H:i:s" string in the database, so an exact
    // string-equality where() clause against a "Y-m-d" input never matches.
    $rows = $component->get('rows');
    expect($rows->first(fn (array $row) => $row['employee']->id === $employee->id)['record'])->not->toBeNull();
});

test('leave manager renders across all tabs with no data yet', function (): void {
    $component = Livewire::actingAs($this->staff)->test(LeaveManager::class)->assertOk();

    $component->set('tab', 'balances')->assertOk();
    $component->set('tab', 'types')->assertOk();
});

test('leave manager renders across all tabs with requests and balances present', function (): void {
    $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);
    $leaveType = LeaveType::factory()->create(['branch_id' => $this->branch->id, 'days_per_year' => 21]);
    LeaveBalance::factory()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => (int) now()->format('Y'), 'entitled_days' => 21]);

    app(SubmitLeaveRequestAction::class)->handle($employee, $leaveType, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-02'));

    $component = Livewire::actingAs($this->staff)->test(LeaveManager::class)->assertOk();

    $component->set('tab', 'balances')->assertOk();
    $component->set('tab', 'types')->assertOk();
});

test('payroll manager renders with no runs yet', function (): void {
    Livewire::actingAs($this->staff)->test(PayrollManager::class)->assertOk();
});

test('payroll manager renders and can view a processed run\'s payslips', function (): void {
    Employee::factory()->create(['branch_id' => $this->branch->id, 'status' => EmployeeStatus::Active, 'hire_date' => '2026-01-01']);

    $run = app(ProcessPayrollRunAction::class)->handle($this->branch->id, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), $this->staff);

    $component = Livewire::actingAs($this->staff)->test(PayrollManager::class)->assertOk();
    $component->call('view', $run->id)->assertOk();

    expect($component->get('viewingRun')->payslips)->toHaveCount(1);
});

test('performance review manager renders with no reviews yet', function (): void {
    Livewire::actingAs($this->staff)->test(PerformanceReviewManager::class)->assertOk();
});

test('performance review manager renders with a review present', function (): void {
    $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);
    PerformanceReview::factory()->create(['employee_id' => $employee->id, 'reviewer_user_id' => $this->staff->id, 'rating' => PerformanceRating::MeetsExpectations]);

    Livewire::actingAs($this->staff)->test(PerformanceReviewManager::class)->assertOk();
});

test('disciplinary record manager renders with no records yet', function (): void {
    Livewire::actingAs($this->staff)->test(DisciplinaryRecordManager::class)->assertOk();
});

test('disciplinary record manager renders with a record present', function (): void {
    $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);
    DisciplinaryRecord::factory()->create(['employee_id' => $employee->id, 'reported_by_user_id' => $this->staff->id, 'severity' => DisciplinarySeverity::VerbalWarning]);

    Livewire::actingAs($this->staff)->test(DisciplinaryRecordManager::class)->assertOk();
});

test('recruitment board renders with no job openings yet', function (): void {
    Livewire::actingAs($this->staff)->test(RecruitmentBoard::class)->assertOk();
});

test('recruitment board renders and shows candidates for a selected opening', function (): void {
    $opening = JobOpening::factory()->create(['branch_id' => $this->branch->id]);
    Candidate::factory()->count(2)->create(['job_opening_id' => $opening->id]);

    $component = Livewire::actingAs($this->staff)->test(RecruitmentBoard::class)->assertOk();

    $component->call('select', $opening->id)->assertOk();

    expect($component->get('selectedOpening')->candidates)->toHaveCount(2);
});
