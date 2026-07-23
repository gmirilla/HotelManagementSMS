<?php

declare(strict_types=1);

use App\Livewire\Inventory\ItemManager;
use App\Livewire\Procurement\PurchaseOrderManager;
use App\Models\Branch;
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
