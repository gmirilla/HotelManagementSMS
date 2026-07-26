<?php

declare(strict_types=1);

use App\Domain\Inventory\Actions\AdjustStockAction;
use App\Domain\Inventory\Actions\IssueStockAction;
use App\Domain\Inventory\Actions\ReceiveStockAction;
use App\Domain\Inventory\Actions\TransferStockAction;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

test('receiving stock increases quantity on hand and rolls the average cost forward', function (): void {
    $item = InventoryItem::factory()->withOpeningStock(10, 100)->create();
    $staff = User::factory()->create();

    // 10 @ 100c existing, receiving 10 @ 200c => weighted average (10*100 + 10*200) / 20 = 150c.
    app(ReceiveStockAction::class)->handle($item, 10, 200, $staff);

    expect($item->fresh()->quantity_on_hand)->toBe(20)
        ->and($item->fresh()->average_cost_cents)->toBe(150)
        ->and($item->stockMovements()->count())->toBe(2);
});

test('receiving a non-positive quantity is rejected', function (): void {
    $item = InventoryItem::factory()->create();
    $staff = User::factory()->create();

    app(ReceiveStockAction::class)->handle($item, 0, 100, $staff);
})->throws(ValidationException::class);

test('issuing stock decreases quantity on hand', function (): void {
    $item = InventoryItem::factory()->withOpeningStock(20)->create();
    $staff = User::factory()->create();

    $movement = app(IssueStockAction::class)->handle($item, 5, $staff);

    expect($item->fresh()->quantity_on_hand)->toBe(15)
        ->and($movement->movement_type)->toBe(StockMovementType::Issue)
        ->and($movement->quantity)->toBe(-5);
});

test('issuing as wastage uses the wastage movement type', function (): void {
    $item = InventoryItem::factory()->withOpeningStock(20)->create();
    $staff = User::factory()->create();

    $movement = app(IssueStockAction::class)->handle($item, 3, $staff, null, StockMovementType::Wastage);

    expect($movement->movement_type)->toBe(StockMovementType::Wastage)
        ->and($item->fresh()->quantity_on_hand)->toBe(17);
});

test('a manual adjustment can move quantity in either direction', function (): void {
    $item = InventoryItem::factory()->withOpeningStock(20)->create();
    $staff = User::factory()->create();

    app(AdjustStockAction::class)->handle($item, -4, $staff, 'Stocktake correction');
    expect($item->fresh()->quantity_on_hand)->toBe(16);

    app(AdjustStockAction::class)->handle($item, 2, $staff, 'Found extra units');
    expect($item->fresh()->quantity_on_hand)->toBe(18);
});

test('a zero-quantity adjustment is rejected', function (): void {
    $item = InventoryItem::factory()->create();
    $staff = User::factory()->create();

    app(AdjustStockAction::class)->handle($item, 0, $staff);
})->throws(ValidationException::class);

test('issuing more stock than is on hand is rejected', function (): void {
    $item = InventoryItem::factory()->withOpeningStock(5)->create();
    $staff = User::factory()->create();

    app(IssueStockAction::class)->handle($item, 6, $staff);
})->throws(ValidationException::class);

test('a rejected over-issue leaves quantity on hand unchanged', function (): void {
    $item = InventoryItem::factory()->withOpeningStock(5)->create();
    $staff = User::factory()->create();

    try {
        app(IssueStockAction::class)->handle($item, 6, $staff);
    } catch (ValidationException) {
        // expected
    }

    expect($item->fresh()->quantity_on_hand)->toBe(5)
        ->and($item->stockMovements()->count())->toBe(1);
});

test('transferring stock moves quantity from the source warehouse into a matching item in the destination', function (): void {
    $sourceWarehouse = Warehouse::factory()->create();
    $destinationWarehouse = Warehouse::factory()->create(['branch_id' => $sourceWarehouse->branch_id]);
    $sourceItem = InventoryItem::factory()->withOpeningStock(20, 150)->create(['warehouse_id' => $sourceWarehouse->id, 'sku' => 'SKU-1']);
    $staff = User::factory()->create();

    [$outbound, $inbound] = app(TransferStockAction::class)->handle($sourceItem, $destinationWarehouse, 8, $staff);

    expect($outbound->movement_type)->toBe(StockMovementType::Transfer)
        ->and($outbound->quantity)->toBe(-8)
        ->and($inbound->movement_type)->toBe(StockMovementType::Transfer)
        ->and($inbound->quantity)->toBe(8)
        ->and($sourceItem->fresh()->quantity_on_hand)->toBe(12);

    $destinationItem = InventoryItem::where('warehouse_id', $destinationWarehouse->id)->where('sku', 'SKU-1')->firstOrFail();
    expect($destinationItem->quantity_on_hand)->toBe(8)
        ->and($destinationItem->average_cost_cents)->toBe(150)
        ->and($destinationItem->name)->toBe($sourceItem->name);
});

test('a second transfer of the same SKU tops up the existing destination item rather than duplicating it', function (): void {
    $sourceWarehouse = Warehouse::factory()->create();
    $destinationWarehouse = Warehouse::factory()->create(['branch_id' => $sourceWarehouse->branch_id]);
    $sourceItem = InventoryItem::factory()->withOpeningStock(20)->create(['warehouse_id' => $sourceWarehouse->id, 'sku' => 'SKU-2']);
    $staff = User::factory()->create();

    app(TransferStockAction::class)->handle($sourceItem, $destinationWarehouse, 5, $staff);
    app(TransferStockAction::class)->handle($sourceItem->fresh(), $destinationWarehouse, 3, $staff);

    expect(InventoryItem::where('warehouse_id', $destinationWarehouse->id)->where('sku', 'SKU-2')->count())->toBe(1)
        ->and(InventoryItem::where('warehouse_id', $destinationWarehouse->id)->where('sku', 'SKU-2')->first()->quantity_on_hand)->toBe(8);
});

test('a transfer cannot exceed the source warehouse\'s stock on hand', function (): void {
    $sourceWarehouse = Warehouse::factory()->create();
    $destinationWarehouse = Warehouse::factory()->create(['branch_id' => $sourceWarehouse->branch_id]);
    $sourceItem = InventoryItem::factory()->withOpeningStock(5)->create(['warehouse_id' => $sourceWarehouse->id]);
    $staff = User::factory()->create();

    app(TransferStockAction::class)->handle($sourceItem, $destinationWarehouse, 6, $staff);
})->throws(ValidationException::class);

test('a transfer to the item\'s own warehouse is rejected', function (): void {
    $warehouse = Warehouse::factory()->create();
    $item = InventoryItem::factory()->withOpeningStock(10)->create(['warehouse_id' => $warehouse->id]);
    $staff = User::factory()->create();

    app(TransferStockAction::class)->handle($item, $warehouse, 1, $staff);
})->throws(ValidationException::class);

test('quantity on hand is always derived from the movement ledger, not stored independently', function (): void {
    $item = InventoryItem::factory()->create(['quantity_on_hand' => 999]);
    $staff = User::factory()->create();

    app(ReceiveStockAction::class)->handle($item, 5, 100, $staff);

    // The stale factory-seeded 999 should be replaced by the true ledger sum (0 existing movements + 5 received).
    expect($item->fresh()->quantity_on_hand)->toBe(5);
});
