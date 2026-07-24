<?php

declare(strict_types=1);

/**
 * Regression coverage for authorization boundary bugs found while auditing
 * every Policy for Deliverable 9 (see git history for the fixes):
 *
 * 1. CorporateAccountPolicy::view()/update() checked permission only, never
 *    tenant_id — any crm.view holder could read/edit another tenant's
 *    corporate accounts.
 * 2. DisciplinaryRecordPolicy/PerformanceReviewPolicy::view() granted any
 *    hr.manage holder access regardless of the employee's branch.
 * 3. LeaveRequestPolicy::review() was authorized against the LeaveRequest
 *    *class*, not the specific instance, so it couldn't check branch at
 *    all — any hr.manage holder could approve/reject any branch's leave.
 * 4. The three HR Livewire "save" actions trusted a client-mutable
 *    employeeId property without re-validating branch membership
 *    server-side (an IDOR: the dropdown only *displays* same-branch
 *    employees, it doesn't *enforce* it).
 * 5. Same IDOR shape, two more places: EventBookingManager::addItem()
 *    accepted an EventService from any branch onto a booking (wrong
 *    pricing attached), and BookingWizard::save() accepted a RoomType
 *    from any branch (wrong rate applied to the reservation).
 * 6. GuestFeedbackPolicy::manage() took no model argument (same class-vs-
 *    instance shape as bug #3) — any crm.manage holder could assign/
 *    resolve any branch's feedback.
 * 7. ArApManager::recordArPayment()/recordApPayment() checked permission
 *    only, never branch — any accounting.manage holder could record a
 *    payment against another branch's AR/AP entry.
 * 8. AttendanceBoard::clockIn()/clockOut()/markStatus() took an employeeId
 *    method argument (not a bound property, but equally client-supplied)
 *    without a branch check — payroll-relevant attendance could be
 *    recorded for another branch's employee.
 */

use App\Domain\Accounting\Enums\ApStatus;
use App\Domain\Accounting\Enums\ArStatus;
use App\Domain\Event\Enums\EventBookingStatus;
use App\Domain\HR\Actions\SubmitLeaveRequestAction;
use App\Livewire\Accounting\ArApManager;
use App\Livewire\CRM\CorporateAccountManager;
use App\Livewire\CRM\FeedbackManager;
use App\Livewire\Events\EventBookingManager;
use App\Livewire\HR\AttendanceBoard;
use App\Livewire\HR\DisciplinaryRecordManager;
use App\Livewire\HR\LeaveManager;
use App\Livewire\HR\PerformanceReviewManager;
use App\Livewire\Reservations\BookingWizard;
use App\Models\ApEntry;
use App\Models\ArEntry;
use App\Models\Branch;
use App\Models\CorporateAccount;
use App\Models\DisciplinaryRecord;
use App\Models\Employee;
use App\Models\EventBooking;
use App\Models\EventService;
use App\Models\EventSpace;
use App\Models\Guest;
use App\Models\GuestFeedback;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PerformanceReview;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('a user cannot view a corporate account belonging to a different tenant', function (): void {
    Permission::firstOrCreate(['name' => 'crm.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary CRM Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('crm.view');

    $ownTenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();

    $user = User::factory()->create(['tenant_id' => $ownTenant->id]);
    $user->assignRole($role);

    $ownAccount = CorporateAccount::factory()->create(['tenant_id' => $ownTenant->id]);
    $otherAccount = CorporateAccount::factory()->create(['tenant_id' => $otherTenant->id]);

    expect($user->can('view', $ownAccount))->toBeTrue()
        ->and($user->can('view', $otherAccount))->toBeFalse();
});

test('a user cannot update a corporate account belonging to a different tenant', function (): void {
    Permission::firstOrCreate(['name' => 'crm.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary CRM Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('crm.manage');

    $user = User::factory()->create();
    $user->assignRole($role);

    $otherAccount = CorporateAccount::factory()->create();

    expect($user->can('update', $otherAccount))->toBeFalse();

    Livewire::actingAs($user)
        ->test(CorporateAccountManager::class)
        ->call('edit', $otherAccount->id)
        ->assertForbidden();
});

test('an hr.manage holder cannot view a disciplinary record from a branch they are not assigned to', function (): void {
    Permission::firstOrCreate(['name' => 'hr.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary HR Officer', 'guard_name' => 'web']);
    $role->givePermissionTo('hr.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $hrOfficer = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $hrOfficer->assignRole($role);
    $ownBranch->staff()->attach($hrOfficer->id, ['role_id' => $role->id, 'is_primary' => true]);

    $ownBranchEmployee = Employee::factory()->create(['branch_id' => $ownBranch->id]);
    $otherBranchEmployee = Employee::factory()->create(['branch_id' => $otherBranch->id]);

    $ownRecord = DisciplinaryRecord::factory()->create(['employee_id' => $ownBranchEmployee->id]);
    $otherRecord = DisciplinaryRecord::factory()->create(['employee_id' => $otherBranchEmployee->id]);

    expect($hrOfficer->can('view', $ownRecord))->toBeTrue()
        ->and($hrOfficer->can('view', $otherRecord))->toBeFalse();
});

test('an hr.manage holder cannot log a disciplinary record for an employee outside their branch', function (): void {
    Permission::firstOrCreate(['name' => 'hr.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary HR Officer 2', 'guard_name' => 'web']);
    $role->givePermissionTo('hr.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $hrOfficer = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $hrOfficer->assignRole($role);
    $ownBranch->staff()->attach($hrOfficer->id, ['role_id' => $role->id, 'is_primary' => true]);

    $otherBranchEmployee = Employee::factory()->create(['branch_id' => $otherBranch->id]);

    Livewire::actingAs($hrOfficer)
        ->test(DisciplinaryRecordManager::class)
        ->set('employeeId', (string) $otherBranchEmployee->id)
        ->set('incidentDate', now()->toDateString())
        ->set('severity', 'verbal_warning')
        ->set('description', 'Attempted cross-branch write')
        ->call('save')
        ->assertForbidden();

    expect(DisciplinaryRecord::where('employee_id', $otherBranchEmployee->id)->exists())->toBeFalse();
});

test('an hr.manage holder cannot view a performance review from a branch they are not assigned to', function (): void {
    Permission::firstOrCreate(['name' => 'hr.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary HR Officer 3', 'guard_name' => 'web']);
    $role->givePermissionTo('hr.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $hrOfficer = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $hrOfficer->assignRole($role);
    $ownBranch->staff()->attach($hrOfficer->id, ['role_id' => $role->id, 'is_primary' => true]);

    $otherBranchEmployee = Employee::factory()->create(['branch_id' => $otherBranch->id]);
    $otherReview = PerformanceReview::factory()->create(['employee_id' => $otherBranchEmployee->id]);

    expect($hrOfficer->can('view', $otherReview))->toBeFalse();
});

test('an hr.manage holder cannot log a performance review for an employee outside their branch', function (): void {
    Permission::firstOrCreate(['name' => 'hr.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary HR Officer 4', 'guard_name' => 'web']);
    $role->givePermissionTo('hr.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $hrOfficer = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $hrOfficer->assignRole($role);
    $ownBranch->staff()->attach($hrOfficer->id, ['role_id' => $role->id, 'is_primary' => true]);

    $otherBranchEmployee = Employee::factory()->create(['branch_id' => $otherBranch->id]);

    Livewire::actingAs($hrOfficer)
        ->test(PerformanceReviewManager::class)
        ->set('employeeId', (string) $otherBranchEmployee->id)
        ->set('reviewPeriod', '2026 H1')
        ->set('reviewDate', now()->toDateString())
        ->set('rating', 'meets_expectations')
        ->call('save')
        ->assertForbidden();

    expect(PerformanceReview::where('employee_id', $otherBranchEmployee->id)->exists())->toBeFalse();
});

test('an hr.manage holder cannot approve a leave request from a branch they are not assigned to', function (): void {
    Permission::firstOrCreate(['name' => 'hr.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary HR Officer 5', 'guard_name' => 'web']);
    $role->givePermissionTo('hr.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $hrOfficer = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $hrOfficer->assignRole($role);
    $ownBranch->staff()->attach($hrOfficer->id, ['role_id' => $role->id, 'is_primary' => true]);

    $otherBranchEmployee = Employee::factory()->create(['branch_id' => $otherBranch->id]);
    $leaveType = LeaveType::factory()->create(['branch_id' => $otherBranch->id]);

    $leaveRequest = app(SubmitLeaveRequestAction::class)->handle(
        $otherBranchEmployee,
        $leaveType,
        now()->addWeek(),
        now()->addWeek()->addDays(2),
    );

    expect($hrOfficer->can('review', $leaveRequest))->toBeFalse();

    Livewire::actingAs($hrOfficer)
        ->test(LeaveManager::class)
        ->call('approve', $leaveRequest->id)
        ->assertForbidden();

    expect($leaveRequest->fresh()->status->value)->toBe('pending');
});

test('an hr.manage holder cannot submit a leave request on behalf of an employee outside their branch', function (): void {
    Permission::firstOrCreate(['name' => 'hr.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary HR Officer 6', 'guard_name' => 'web']);
    $role->givePermissionTo('hr.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $hrOfficer = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $hrOfficer->assignRole($role);
    $ownBranch->staff()->attach($hrOfficer->id, ['role_id' => $role->id, 'is_primary' => true]);

    $otherBranchEmployee = Employee::factory()->create(['branch_id' => $otherBranch->id]);
    $otherBranchLeaveType = LeaveType::factory()->create(['branch_id' => $otherBranch->id]);

    Livewire::actingAs($hrOfficer)
        ->test(LeaveManager::class)
        ->set('employeeId', (string) $otherBranchEmployee->id)
        ->set('leaveTypeId', (string) $otherBranchLeaveType->id)
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('endDate', now()->addWeek()->addDay()->toDateString())
        ->call('submitRequest')
        ->assertForbidden();

    expect(LeaveRequest::where('employee_id', $otherBranchEmployee->id)->exists())->toBeFalse();
});

test('a booking cannot have a different branch\'s event service attached to it', function (): void {
    Permission::firstOrCreate(['name' => 'events.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Events Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('events.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $user = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $user->assignRole($role);
    $ownBranch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    $ownSpace = EventSpace::factory()->create(['branch_id' => $ownBranch->id]);
    $booking = EventBooking::factory()->create(['branch_id' => $ownBranch->id, 'event_space_id' => $ownSpace->id, 'status' => EventBookingStatus::Tentative]);
    $otherBranchService = EventService::factory()->create(['branch_id' => $otherBranch->id]);

    Livewire::actingAs($user)
        ->test(EventBookingManager::class)
        ->call('select', $booking->id)
        ->set('selectedServiceId', (string) $otherBranchService->id)
        ->set('itemQuantity', '1')
        ->call('addItem')
        ->assertForbidden();

    expect($booking->items()->count())->toBe(0);
});

test('a reservation cannot be booked against a different branch\'s room type', function (): void {
    Permission::firstOrCreate(['name' => 'reservations.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Booking Wizard User', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $user = User::factory()->create(['tenant_id' => $ownBranch->tenant_id, 'current_branch_id' => $ownBranch->id]);
    $user->assignRole($role);
    $ownBranch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    $guest = Guest::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $otherBranchRoomType = RoomType::factory()->create(['branch_id' => $otherBranch->id]);

    Livewire::actingAs($user)
        ->test(BookingWizard::class)
        ->set('selectedGuestId', $guest->id)
        ->set('selectedRoomTypeId', $otherBranchRoomType->id)
        ->set('arrivalDate', now()->addWeek()->toDateString())
        ->set('departureDate', now()->addWeek()->addDays(2)->toDateString())
        ->set('adults', 2)
        ->call('confirm')
        ->assertForbidden();
});

test('a crm.manage holder cannot assign or resolve guest feedback from a branch they are not assigned to', function (): void {
    Permission::firstOrCreate(['name' => 'crm.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary CRM Feedback Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('crm.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $user = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $user->assignRole($role);
    $ownBranch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    $otherFeedback = GuestFeedback::factory()->create(['branch_id' => $otherBranch->id]);

    expect($user->can('manage', $otherFeedback))->toBeFalse();

    Livewire::actingAs($user)
        ->test(FeedbackManager::class)
        ->call('assignToMe', $otherFeedback->id)
        ->assertForbidden();

    expect($otherFeedback->fresh()->assigned_to_user_id)->toBeNull();
});

test('an accounting.manage holder cannot record a payment against a different branch\'s receivable', function (): void {
    Permission::firstOrCreate(['name' => 'accounting.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Accountant', 'guard_name' => 'web']);
    $role->givePermissionTo('accounting.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $user = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $user->assignRole($role);
    $ownBranch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    $otherArEntry = ArEntry::factory()->create(['branch_id' => $otherBranch->id, 'amount_cents' => 10000, 'paid_cents' => 0, 'status' => ArStatus::Open]);
    $otherApEntry = ApEntry::factory()->create(['branch_id' => $otherBranch->id, 'amount_cents' => 10000, 'paid_cents' => 0, 'status' => ApStatus::Open]);

    Livewire::actingAs($user)
        ->test(ArApManager::class)
        ->call('startArPayment', $otherArEntry->id)
        ->set('paymentAmount', '50')
        ->call('recordArPayment')
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test(ArApManager::class)
        ->call('startApPayment', $otherApEntry->id)
        ->set('paymentAmount', '50')
        ->call('recordApPayment')
        ->assertForbidden();

    expect($otherArEntry->fresh()->paid_cents)->toBe(0)
        ->and($otherApEntry->fresh()->paid_cents)->toBe(0);
});

test('an hr.manage holder cannot clock in or record attendance for an employee outside their branch', function (): void {
    Permission::firstOrCreate(['name' => 'hr.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary HR Officer 7', 'guard_name' => 'web']);
    $role->givePermissionTo('hr.manage');

    $ownBranch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $ownBranch->tenant_id]);

    $user = User::factory()->create(['tenant_id' => $ownBranch->tenant_id, 'current_branch_id' => $ownBranch->id]);
    $user->assignRole($role);
    $ownBranch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    $otherBranchEmployee = Employee::factory()->create(['branch_id' => $otherBranch->id]);

    Livewire::actingAs($user)
        ->test(AttendanceBoard::class)
        ->call('clockIn', $otherBranchEmployee->id)
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test(AttendanceBoard::class)
        ->call('markStatus', $otherBranchEmployee->id, 'absent')
        ->assertForbidden();

    expect($otherBranchEmployee->attendanceRecords()->count())->toBe(0);
});
