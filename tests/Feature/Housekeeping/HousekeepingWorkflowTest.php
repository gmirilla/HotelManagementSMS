<?php

declare(strict_types=1);

use App\Domain\Housekeeping\Actions\AssignHousekeepingTaskAction;
use App\Domain\Housekeeping\Actions\CompleteHousekeepingTaskAction;
use App\Domain\Housekeeping\Actions\InspectHousekeepingTaskAction;
use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('assigning a task records the assignee', function (): void {
    $task = HousekeepingTask::factory()->create();
    $staff = User::factory()->create();

    app(AssignHousekeepingTaskAction::class)->handle($task, $staff);

    expect($task->fresh()->assigned_to_user_id)->toBe($staff->id);
});

test('completing a task moves it to awaiting inspection, not straight to completed', function (): void {
    $task = HousekeepingTask::factory()->create(['status' => HousekeepingTaskStatus::InProgress]);

    app(CompleteHousekeepingTaskAction::class)->handle($task, ['Bed made' => true]);

    expect($task->fresh()->status)->toBe(HousekeepingTaskStatus::AwaitingInspection);
});

test('an already-completed task cannot be completed again', function (): void {
    $task = HousekeepingTask::factory()->create(['status' => HousekeepingTaskStatus::Completed]);

    app(CompleteHousekeepingTaskAction::class)->handle($task, []);
})->throws(ValidationException::class);

test('passing inspection marks the task completed and returns a dirty room to vacant clean', function (): void {
    $room = Room::factory()->create(['status' => RoomStatus::VacantDirty, 'housekeeping_status' => HousekeepingStatus::Dirty]);
    $task = HousekeepingTask::factory()->create(['room_id' => $room->id, 'status' => HousekeepingTaskStatus::AwaitingInspection]);
    $inspector = User::factory()->create();

    app(InspectHousekeepingTaskAction::class)->handle($task, $inspector, true);

    expect($task->fresh()->status)->toBe(HousekeepingTaskStatus::Completed)
        ->and($room->fresh()->status)->toBe(RoomStatus::VacantClean)
        ->and($room->fresh()->housekeeping_status)->toBe(HousekeepingStatus::Clean);
});

test('failing inspection leaves the room dirty and flags the task', function (): void {
    $room = Room::factory()->create(['status' => RoomStatus::VacantDirty]);
    $task = HousekeepingTask::factory()->create(['room_id' => $room->id, 'status' => HousekeepingTaskStatus::AwaitingInspection]);
    $inspector = User::factory()->create();

    app(InspectHousekeepingTaskAction::class)->handle($task, $inspector, false);

    expect($task->fresh()->status)->toBe(HousekeepingTaskStatus::FailedInspection)
        ->and($room->fresh()->status)->toBe(RoomStatus::VacantDirty);
});

test('a pending task cannot be inspected directly', function (): void {
    $task = HousekeepingTask::factory()->create(['status' => HousekeepingTaskStatus::Pending]);
    $inspector = User::factory()->create();

    app(InspectHousekeepingTaskAction::class)->handle($task, $inspector, true);
})->throws(ValidationException::class);
