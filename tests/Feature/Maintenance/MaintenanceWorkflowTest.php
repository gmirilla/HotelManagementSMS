<?php

declare(strict_types=1);

use App\Domain\Maintenance\Actions\CompleteMaintenanceWorkOrderAction;
use App\Domain\Maintenance\Actions\CreateMaintenanceWorkOrderAction;
use App\Domain\Maintenance\Actions\VerifyMaintenanceWorkOrderAction;
use App\Domain\Maintenance\Enums\WorkOrderPriority;
use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\MaintenanceWorkOrder;
use App\Models\Room;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('creating a work order with takeRoomOutOfOrder blocks the room and logs the status change', function (): void {
    $room = Room::factory()->create(['status' => RoomStatus::VacantClean]);
    $staff = User::factory()->create();

    $workOrder = app(CreateMaintenanceWorkOrderAction::class)->handle(
        branchId: $room->branch_id,
        reportedBy: $staff,
        description: 'Leaking faucet',
        priority: WorkOrderPriority::High,
        room: $room,
        takeRoomOutOfOrder: true,
    );

    expect($workOrder->status)->toBe(WorkOrderStatus::Open)
        ->and($room->fresh()->status)->toBe(RoomStatus::OutOfOrder)
        ->and($room->fresh()->statusLogs()->latest('id')->first()->reason)->toContain((string) $workOrder->id);
});

test('creating a work order without takeRoomOutOfOrder leaves the room bookable', function (): void {
    $room = Room::factory()->create(['status' => RoomStatus::VacantClean]);
    $staff = User::factory()->create();

    // A minor issue (a squeaky door) shouldn't pull a room from inventory —
    // FR-MAINT-005 ties availability to an explicit "Out of Order" status,
    // not to the mere existence of an open work order.
    app(CreateMaintenanceWorkOrderAction::class)->handle(
        branchId: $room->branch_id,
        reportedBy: $staff,
        description: 'Squeaky door',
        priority: WorkOrderPriority::Low,
        room: $room,
    );

    expect($room->fresh()->status)->toBe(RoomStatus::VacantClean)
        ->and($room->fresh()->isBookable())->toBeTrue();
});

test('completing then verifying a work order releases an out-of-order room back to vacant dirty', function (): void {
    $room = Room::factory()->create(['status' => RoomStatus::OutOfOrder]);
    $workOrder = MaintenanceWorkOrder::factory()->create([
        'branch_id' => $room->branch_id,
        'room_id' => $room->id,
        'status' => WorkOrderStatus::Open,
    ]);
    $staff = User::factory()->create();

    app(CompleteMaintenanceWorkOrderAction::class)->handle($workOrder, 1500, 3000);
    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Completed)
        ->and($workOrder->fresh()->totalCostCents())->toBe(4500)
        ->and($room->fresh()->status)->toBe(RoomStatus::OutOfOrder);

    app(VerifyMaintenanceWorkOrderAction::class)->handle($workOrder->fresh(), $staff);

    expect($room->fresh()->status)->toBe(RoomStatus::VacantDirty);
});

test('verifying does not release the room while another work order is still open', function (): void {
    $room = Room::factory()->create(['status' => RoomStatus::OutOfOrder]);
    $workOrderOne = MaintenanceWorkOrder::factory()->create(['branch_id' => $room->branch_id, 'room_id' => $room->id, 'status' => WorkOrderStatus::Open]);
    MaintenanceWorkOrder::factory()->create(['branch_id' => $room->branch_id, 'room_id' => $room->id, 'status' => WorkOrderStatus::Open]);
    $staff = User::factory()->create();

    app(CompleteMaintenanceWorkOrderAction::class)->handle($workOrderOne, 0, 0);
    app(VerifyMaintenanceWorkOrderAction::class)->handle($workOrderOne->fresh(), $staff);

    expect($room->fresh()->status)->toBe(RoomStatus::OutOfOrder);
});

test('an open work order cannot be verified before being completed', function (): void {
    $workOrder = MaintenanceWorkOrder::factory()->create(['status' => WorkOrderStatus::Open]);
    $staff = User::factory()->create();

    app(VerifyMaintenanceWorkOrderAction::class)->handle($workOrder, $staff);
})->throws(ValidationException::class);
