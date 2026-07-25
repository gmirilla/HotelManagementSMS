<?php

declare(strict_types=1);

use App\Domain\Inventory\Actions\AdjustStockAction;
use App\Domain\Inventory\Actions\IssueStockAction;
use App\Domain\Inventory\Actions\ReceiveStockAction;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\User;
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

test('quantity on hand is always derived from the movement ledger, not stored independently', function (): void {
    $item = InventoryItem::factory()->create(['quantity_on_hand' => 999]);
    $staff = User::factory()->create();

    app(ReceiveStockAction::class)->handle($item, 5, 100, $staff);

    // The stale factory-seeded 999 should be replaced by the true ledger sum (0 existing movements + 5 received).
    expect($item->fresh()->quantity_on_hand)->toBe(5);
});
