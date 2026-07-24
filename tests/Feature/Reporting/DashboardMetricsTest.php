<?php

declare(strict_types=1);

use App\Domain\Accounting\Enums\ArStatus;
use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Domain\Reporting\Support\DashboardMetrics;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\ArEntry;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\HousekeepingTask;
use App\Models\MaintenanceWorkOrder;
use App\Models\RestaurantOrder;
use App\Models\Room;

test('occupancy rate is the share of active rooms currently occupied', function (): void {
    $branch = Branch::factory()->create();
    Room::factory()->create(['branch_id' => $branch->id, 'status' => RoomStatus::Occupied, 'is_active' => true]);
    Room::factory(3)->create(['branch_id' => $branch->id, 'status' => RoomStatus::VacantClean, 'is_active' => true]);
    Room::factory()->create(['branch_id' => $branch->id, 'status' => RoomStatus::Occupied, 'is_active' => false]);

    expect(app(DashboardMetrics::class)->occupancyRate($branch->id))->toBe(25.0);
});

test('occupancy rate is zero when a branch has no active rooms', function (): void {
    $branch = Branch::factory()->create();

    expect(app(DashboardMetrics::class)->occupancyRate($branch->id))->toBe(0.0);
});

test('room status breakdown counts every status, including zero counts', function (): void {
    $branch = Branch::factory()->create();
    Room::factory(2)->create(['branch_id' => $branch->id, 'status' => RoomStatus::VacantClean, 'is_active' => true]);
    Room::factory()->create(['branch_id' => $branch->id, 'status' => RoomStatus::VacantDirty, 'is_active' => true]);
    Room::factory()->create(['branch_id' => $branch->id, 'status' => RoomStatus::Occupied, 'is_active' => true]);
    Room::factory()->create(['branch_id' => $branch->id, 'status' => RoomStatus::VacantClean, 'is_active' => false]);

    $breakdown = app(DashboardMetrics::class)->roomStatusBreakdown($branch->id);

    expect($breakdown)->toBe([
        'Vacant / Clean' => 2,
        'Vacant / Dirty' => 1,
        'Occupied' => 1,
        'Out of Order' => 0,
        'Out of Service' => 0,
    ]);
});

test('pending housekeeping counts everything not yet completed', function (): void {
    $branch = Branch::factory()->create();
    HousekeepingTask::factory(2)->create(['branch_id' => $branch->id, 'status' => HousekeepingTaskStatus::Pending]);
    HousekeepingTask::factory()->create(['branch_id' => $branch->id, 'status' => HousekeepingTaskStatus::InProgress]);
    HousekeepingTask::factory()->create(['branch_id' => $branch->id, 'status' => HousekeepingTaskStatus::Completed]);

    expect(app(DashboardMetrics::class)->pendingHousekeepingCount($branch->id))->toBe(3);
});

test('open maintenance counts only blocking work order statuses', function (): void {
    $branch = Branch::factory()->create();
    MaintenanceWorkOrder::factory()->create(['branch_id' => $branch->id, 'status' => WorkOrderStatus::Open]);
    MaintenanceWorkOrder::factory()->create(['branch_id' => $branch->id, 'status' => WorkOrderStatus::InProgress]);
    MaintenanceWorkOrder::factory()->create(['branch_id' => $branch->id, 'status' => WorkOrderStatus::Completed]);
    MaintenanceWorkOrder::factory()->create(['branch_id' => $branch->id, 'status' => WorkOrderStatus::Verified]);

    expect(app(DashboardMetrics::class)->openMaintenanceCount($branch->id))->toBe(2);
});

test('restaurant sales today sums only today\'s closed orders', function (): void {
    $branch = Branch::factory()->create();
    RestaurantOrder::factory()->create(['branch_id' => $branch->id, 'status' => OrderStatus::Closed, 'total_cents' => 5000, 'created_at' => now()]);
    RestaurantOrder::factory()->create(['branch_id' => $branch->id, 'status' => OrderStatus::Closed, 'total_cents' => 3000, 'created_at' => now()->subDay()]);
    RestaurantOrder::factory()->create(['branch_id' => $branch->id, 'status' => OrderStatus::Open, 'total_cents' => 2000, 'created_at' => now()]);

    expect(app(DashboardMetrics::class)->restaurantSalesTodayCents($branch->id))->toBe(5000);
});

test('outstanding invoices combines open folio balances and outstanding AR', function (): void {
    $branch = Branch::factory()->create();
    Folio::factory()->create(['branch_id' => $branch->id, 'status' => FolioStatus::Open, 'balance_cents' => 10000]);
    Folio::factory()->closed()->create(['branch_id' => $branch->id, 'balance_cents' => 0]);
    ArEntry::factory()->create(['branch_id' => $branch->id, 'amount_cents' => 50000, 'paid_cents' => 20000, 'status' => ArStatus::PartiallyPaid]);
    ArEntry::factory()->create(['branch_id' => $branch->id, 'amount_cents' => 10000, 'paid_cents' => 10000, 'status' => ArStatus::Paid]);

    expect(app(DashboardMetrics::class)->outstandingInvoicesCents($branch->id))->toBe(40000);
});

test('revenue trend sums folio charges and directly-paid restaurant sales without double-counting room-service', function (): void {
    $branch = Branch::factory()->create();
    $folio = Folio::factory()->create(['branch_id' => $branch->id]);
    FolioCharge::factory()->create(['folio_id' => $folio->id, 'amount_cents' => 10000, 'charge_date' => now()->toDateString()]);

    // A room-service order already billed to the folio — its total was
    // already posted as a FolioCharge (mirroring CloseRestaurantOrderAction),
    // so it must NOT be added again just because the order itself exists.
    RestaurantOrder::factory()->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Closed,
        'total_cents' => 4000,
        'folio_id' => $folio->id,
        'created_at' => now(),
    ]);

    // A dine-in order paid directly, never touched a folio — this one does
    // count.
    RestaurantOrder::factory()->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Closed,
        'total_cents' => 5000,
        'folio_id' => null,
        'created_at' => now(),
    ]);

    $trend = app(DashboardMetrics::class)->revenueTrend($branch->id, days: 14);

    expect($trend)->toHaveCount(14)
        ->and(array_key_last($trend))->toBe(now()->toDateString())
        ->and($trend[now()->toDateString()])->toBe(15000)
        ->and(array_sum($trend))->toBe(15000);
});
