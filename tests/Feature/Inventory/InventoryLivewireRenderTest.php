<?php

declare(strict_types=1);

use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Livewire\Inventory\ItemManager;
use App\Livewire\Procurement\PurchaseOrderManager;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'inventory.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'procurement.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
    $role->givePermissionTo(['inventory.manage', 'procurement.manage']);

    $this->branch = Branch::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('the inventory item manager renders with no items yet, auto-creating the default warehouse', function (): void {
    Livewire::actingAs($this->staff)->test(ItemManager::class)->assertOk();

    expect(Warehouse::where('branch_id', $this->branch->id)->exists())->toBeTrue();
});

test('the purchase order manager renders with no orders yet', function (): void {
    Livewire::actingAs($this->staff)->test(PurchaseOrderManager::class)->assertOk();
});

test('creating a second warehouse enables transferring stock into it through the item manager', function (): void {
    $component = Livewire::actingAs($this->staff)->test(ItemManager::class)->assertOk();

    $mainWarehouse = Warehouse::where('branch_id', $this->branch->id)->firstOrFail();
    $item = InventoryItem::factory()->withOpeningStock(10)->create(['warehouse_id' => $mainWarehouse->id]);

    $component->call('createWarehouse')
        ->set('newWarehouseName', 'Kitchen Store')
        ->call('saveWarehouse')
        ->assertOk();

    $kitchenWarehouse = Warehouse::where('branch_id', $this->branch->id)->where('name', 'Kitchen Store')->firstOrFail();

    // saveWarehouse() switches the active warehouse to the one just created,
    // so re-select the source warehouse the item actually lives in before
    // starting the transfer against it.
    $component->set('activeWarehouseId', $mainWarehouse->id)
        ->call('startMovement', $item->id, 'transfer')
        ->set('movementQuantity', '4')
        ->set('transferDestinationWarehouseId', $kitchenWarehouse->id)
        ->call('submitMovement')
        ->assertOk();

    expect($item->fresh()->quantity_on_hand)->toBe(6)
        ->and(InventoryItem::where('warehouse_id', $kitchenWarehouse->id)->where('sku', $item->sku)->first()?->quantity_on_hand)->toBe(4);
});

test('the purchase order manager can save a draft, send it, then cancel or close it', function (): void {
    $warehouse = Warehouse::factory()->create(['branch_id' => $this->branch->id]);
    $item = InventoryItem::factory()->create(['warehouse_id' => $warehouse->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $this->branch->tenant_id]);

    $component = Livewire::actingAs($this->staff)
        ->test(PurchaseOrderManager::class)
        ->call('create')
        ->set('supplierId', $supplier->id)
        ->set('lines.0.inventory_item_id', $item->id)
        ->set('lines.0.quantity', '5')
        ->set('lines.0.unit_cost', '10.00')
        ->set('saveAsDraft', true)
        ->call('save')
        ->assertOk();

    $po = PurchaseOrder::where('supplier_id', $supplier->id)->firstOrFail();
    expect($po->status)->toBe(PurchaseOrderStatus::Draft);

    $component->call('sendPurchaseOrder', $po->id)->assertOk();
    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Sent);

    $component->call('cancelPurchaseOrder', $po->id)->assertOk();
    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Cancelled);
});
