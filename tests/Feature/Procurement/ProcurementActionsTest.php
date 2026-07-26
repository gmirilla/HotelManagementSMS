<?php

declare(strict_types=1);

use App\Domain\Accounting\Enums\ApStatus;
use App\Domain\Procurement\Actions\CancelPurchaseOrderAction;
use App\Domain\Procurement\Actions\ClosePurchaseOrderAction;
use App\Domain\Procurement\Actions\CreatePurchaseOrderAction;
use App\Domain\Procurement\Actions\ReceiveGoodsAction;
use App\Domain\Procurement\Actions\SendPurchaseOrderAction;
use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Models\ApEntry;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\JournalEntry;
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

test('a purchase order can be created as a draft and sent to the supplier later', function (): void {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost_cents' => 500],
    ], asDraft: true);

    expect($po->status)->toBe(PurchaseOrderStatus::Draft);

    app(SendPurchaseOrderAction::class)->handle($po);

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Sent);
});

test('a purchase order that is not a draft cannot be sent', function (): void {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost_cents' => 500],
    ]);

    app(SendPurchaseOrderAction::class)->handle($po);
})->throws(ValidationException::class);

test('a draft or sent purchase order with nothing received can be cancelled', function (): void {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost_cents' => 500],
    ]);

    app(CancelPurchaseOrderAction::class)->handle($po);

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Cancelled);
});

test('a purchase order that has already received goods cannot be cancelled', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost_cents' => 500],
    ]);
    app(ReceiveGoodsAction::class)->handle($po, [$po->items->first()->id => 5], $staff);

    app(CancelPurchaseOrderAction::class)->handle($po->fresh());
})->throws(ValidationException::class);

test('a partially received purchase order can be closed without receiving the rest', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost_cents' => 500],
    ]);
    app(ReceiveGoodsAction::class)->handle($po, [$po->items->first()->id => 4], $staff);

    app(ClosePurchaseOrderAction::class)->handle($po->fresh());

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Closed);
});

test('a draft purchase order cannot be closed', function (): void {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost_cents' => 500],
    ], asDraft: true);

    app(ClosePurchaseOrderAction::class)->handle($po);
})->throws(ValidationException::class);

test('a purchase order requires at least one line item', function (): void {
    $branch = Branch::factory()->create();
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, []);
})->throws(ValidationException::class);

test('receiving all ordered goods moves stock and marks the PO fully received', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
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

    $apEntry = ApEntry::where('purchase_order_id', $po->id)->firstOrFail();
    expect($apEntry->supplier_id)->toBe($supplier->id)
        ->and($apEntry->amount_cents)->toBe(20 * 300)
        ->and($apEntry->status)->toBe(ApStatus::Open);

    $ledgerEntry = JournalEntry::where('reference_type', $apEntry->getMorphClass())->where('reference_id', $apEntry->id)->firstOrFail();
    expect($ledgerEntry->isBalanced())->toBeTrue()
        ->and($ledgerEntry->totalDebitCents())->toBe(20 * 300);
});

test('a supplier payable is due per the supplier\'s payment terms', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $netFifteenSupplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id, 'payment_terms' => 'Net 15']);
    $dueOnReceiptSupplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id, 'payment_terms' => 'Due on receipt']);
    $staff = User::factory()->create();

    $netFifteenPo = app(CreatePurchaseOrderAction::class)->handle($branch->id, $netFifteenSupplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 5, 'unit_cost_cents' => 100],
    ]);
    app(ReceiveGoodsAction::class)->handle($netFifteenPo, [$netFifteenPo->items->first()->id => 5], $staff);
    $netFifteenEntry = ApEntry::where('purchase_order_id', $netFifteenPo->id)->firstOrFail();

    $dueOnReceiptPo = app(CreatePurchaseOrderAction::class)->handle($branch->id, $dueOnReceiptSupplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 5, 'unit_cost_cents' => 100],
    ]);
    app(ReceiveGoodsAction::class)->handle($dueOnReceiptPo, [$dueOnReceiptPo->items->first()->id => 5], $staff);
    $dueOnReceiptEntry = ApEntry::where('purchase_order_id', $dueOnReceiptPo->id)->firstOrFail();

    expect($netFifteenEntry->due_date->toDateString())->toBe(now()->addDays(15)->toDateString())
        ->and($dueOnReceiptEntry->due_date->toDateString())->toBe(now()->toDateString());
});

test('receiving a partial quantity marks the PO partially received', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
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

test('receiving more than was ordered on a line is rejected', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 20, 'unit_cost_cents' => 300],
    ]);

    app(ReceiveGoodsAction::class)->handle($po, [$po->items->first()->id => 21], $staff);
})->throws(ValidationException::class);

test('a rejected over-receipt leaves stock and the PO line untouched', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id, 'quantity_on_hand' => 0]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $item->id, 'quantity' => 20, 'unit_cost_cents' => 300],
    ]);

    try {
        app(ReceiveGoodsAction::class)->handle($po, [$po->items->first()->id => 21], $staff);
    } catch (ValidationException) {
        // expected
    }

    expect($item->fresh()->quantity_on_hand)->toBe(0)
        ->and($po->items->first()->fresh()->quantity_received)->toBe(0)
        ->and(ApEntry::where('purchase_order_id', $po->id)->exists())->toBeFalse();
});

test('a partial over-receipt across multiple lines rolls back the whole delivery', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $itemA = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id, 'quantity_on_hand' => 0]);
    $itemB = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id, 'quantity_on_hand' => 0]);
    $supplier = Supplier::factory()->create(['tenant_id' => $branch->tenant_id]);
    $staff = User::factory()->create();

    $po = app(CreatePurchaseOrderAction::class)->handle($branch->id, $supplier->id, $staff, [
        ['inventory_item_id' => $itemA->id, 'quantity' => 10, 'unit_cost_cents' => 100],
        ['inventory_item_id' => $itemB->id, 'quantity' => 10, 'unit_cost_cents' => 100],
    ]);
    [$lineA, $lineB] = $po->items;

    try {
        // Line A is a legitimate full receipt; line B over-receives — the
        // whole delivery, including the otherwise-valid line A, must not
        // partially apply.
        app(ReceiveGoodsAction::class)->handle($po, [$lineA->id => 10, $lineB->id => 11], $staff);
    } catch (ValidationException) {
        // expected
    }

    expect($itemA->fresh()->quantity_on_hand)->toBe(0)
        ->and($lineA->fresh()->quantity_received)->toBe(0);
});
