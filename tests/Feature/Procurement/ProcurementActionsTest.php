<?php

declare(strict_types=1);

use App\Domain\Procurement\Actions\CreatePurchaseOrderAction;
use App\Domain\Procurement\Actions\ReceiveGoodsAction;
use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

test('creating a purchase order computes the total from its line items', function (): void {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $itemA = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $itemB = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $itemA->id, 'quantity' => 10, 'unit_cost_cents' => 500],
        ['inventory_item_id' => $itemB->id, 'quantity' => 5, 'unit_cost_cents' => 200],
    ]);

    expect($po->status)->toBe(PurchaseOrderStatus::Sent)
        ->and($po->total_cents)->toBe(10 * 500 + 5 * 200)
        ->and($po->items)->toHaveCount(2);
});

test('a purchase order requires at least one line item', function (): void {
    $branch = Branch::factory()->create();
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, []);
})->throws(ValidationException::class);

test('receiving all ordered goods moves stock and marks the PO fully received', function (): void {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id, 'quantity_on_hand' => 0]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 20, 'unit_cost_cents' => 300],
    ]);

    $receipt = app(ReceiveGoodsAction::class)->handle($po, [$po->items->first()->id => 20], $staff);

    expect($receipt->has_discrepancy)->toBeFalse()
        ->and($item->fresh()->quantity_on_hand)->toBe(20)
        ->and($po->fresh()->status)->toBe(PurchaseOrderStatus::Received);
});

test('receiving a partial quantity marks the PO partially received', function (): void {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 20, 'unit_cost_cents' => 300],
    ]);

    app(ReceiveGoodsAction::class)->handle($po, [$po->items->first()->id => 8], $staff);

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived)
        ->and($po->items->first()->fresh()->quantity_received)->toBe(8)
        ->and($po->items->first()->fresh()->outstandingQuantity())->toBe(12);
});
