<?php

declare(strict_types=1);

use App\Domain\Restaurant\Enums\TableStatus;
use App\Livewire\Restaurant\KitchenDisplay;
use App\Livewire\Restaurant\MenuManager;
use App\Livewire\Restaurant\PosTerminal;
use App\Models\Branch;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOutlet;
use App\Models\RestaurantTable;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * These are deliberately full-page render tests, not just Action calls:
 * a real bug (Livewire computed properties declared to return
 * Illuminate\Database\Eloquent\Collection but returning collect()/groupBy()
 * results, which are the base Illuminate\Support\Collection) only surfaced
 * as a 500 on an actual page render, never in the Action-level unit tests.
 */
beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'restaurant.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Restaurant Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('restaurant.manage');

    $this->branch = Branch::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('the POS terminal renders with no outlets at all', function (): void {
    Livewire::actingAs($this->staff)->test(PosTerminal::class)->assertOk();
});

test('the POS terminal renders once an outlet, table, and menu exist', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    RestaurantTable::factory()->create(['outlet_id' => $outlet->id]);
    $category = MenuCategory::factory()->create(['outlet_id' => $outlet->id]);
    MenuItem::factory()->create(['menu_category_id' => $category->id]);

    Livewire::actingAs($this->staff)->test(PosTerminal::class)->assertOk();
});

test('the POS terminal auto-selects the branch\'s first outlet on initial load', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Free]);

    Livewire::actingAs($this->staff)
        ->test(PosTerminal::class)
        ->assertSet('selectedOutletId', $outlet->id);
});

test('the menu manager renders with no outlets at all', function (): void {
    Livewire::actingAs($this->staff)->test(MenuManager::class)->assertOk();
});

test('the menu manager renders once an outlet and menu exist', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    RestaurantTable::factory()->create(['outlet_id' => $outlet->id]);
    MenuCategory::factory()->create(['outlet_id' => $outlet->id]);

    Livewire::actingAs($this->staff)
        ->test(MenuManager::class)
        ->set('selectedOutletId', $outlet->id)
        ->assertOk();
});

test('the menu manager auto-selects the branch\'s first outlet on initial load', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->staff)
        ->test(MenuManager::class)
        ->assertSet('selectedOutletId', $outlet->id);
});

test('the kitchen display renders with no active tickets', function (): void {
    Livewire::actingAs($this->staff)->test(KitchenDisplay::class)->assertOk();
});

test('the menu manager can reserve and unreserve a free table, but not an occupied one', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    $freeTable = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Free]);
    $occupiedTable = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Occupied]);

    $component = Livewire::actingAs($this->staff)
        ->test(MenuManager::class)
        ->set('selectedOutletId', $outlet->id)
        ->call('toggleTableReservation', $freeTable->id)
        ->assertOk();

    expect($freeTable->fresh()->status)->toBe(TableStatus::Reserved);

    $component->call('toggleTableReservation', $freeTable->id);
    expect($freeTable->fresh()->status)->toBe(TableStatus::Free);

    $component->call('toggleTableReservation', $occupiedTable->id)->assertHasErrors();
    expect($occupiedTable->fresh()->status)->toBe(TableStatus::Occupied);
});
