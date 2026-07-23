<?php

declare(strict_types=1);

use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Livewire\Housekeeping\TaskBoard;
use App\Models\Branch;
use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'housekeeping.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Housekeeping Supervisor', 'guard_name' => 'web']);
    $role->givePermissionTo('housekeeping.manage');

    $this->branch = Branch::factory()->create();
    $this->room = Room::factory()->create(['branch_id' => $this->branch->id]);
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('a supervisor can create a task and see it in the pending column', function (): void {
    Livewire::actingAs($this->staff)
        ->test(TaskBoard::class)
        ->set('roomId', $this->room->id)
        ->call('createTask')
        ->assertHasNoErrors();

    expect(HousekeepingTask::where('room_id', $this->room->id)->exists())->toBeTrue();
});

test('a full assign -> complete -> inspect cycle works end to end through the component', function (): void {
    $task = HousekeepingTask::factory()->create([
        'branch_id' => $this->branch->id,
        'room_id' => $this->room->id,
        'status' => HousekeepingTaskStatus::Pending,
        'scheduled_date' => now()->toDateString(),
    ]);

    $component = Livewire::actingAs($this->staff)
        ->test(TaskBoard::class)
        ->call('assignToMe', $task->id);

    expect($task->fresh()->assigned_to_user_id)->toBe($this->staff->id);

    $component->call('startCompleting', $task->id)
        ->set('checklistDraft.Bed made', true)
        ->call('completeTask');

    expect($task->fresh()->status)->toBe(HousekeepingTaskStatus::AwaitingInspection);

    $component->call('inspect', $task->id, true);

    expect($task->fresh()->status)->toBe(HousekeepingTaskStatus::Completed);
});
