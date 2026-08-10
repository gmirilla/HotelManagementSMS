<?php

declare(strict_types=1);

use App\Domain\Restaurant\Actions\CreateRestaurantOrderAction;
use App\Domain\Restaurant\Enums\OrderType;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Livewire\Restaurant\KitchenDisplay;
use App\Livewire\Restaurant\MenuManager;
use App\Livewire\Restaurant\PosTerminal;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOutlet;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
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

test('toggling availability of a menu item from a different outlet is forbidden', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    $foreignCategory = MenuCategory::factory()->create();
    $foreignItem = MenuItem::factory()->create(['menu_category_id' => $foreignCategory->id, 'is_available' => true]);

    Livewire::actingAs($this->staff)
        ->test(MenuManager::class)
        ->set('selectedOutletId', $outlet->id)
        ->call('toggleAvailability', $foreignItem->id)
        ->assertForbidden();

    expect($foreignItem->fresh()->is_available)->toBeTrue();
});

test('selecting an outlet outside the branch is forbidden in the menu manager', function (): void {
    $foreignOutlet = RestaurantOutlet::factory()->create();

    Livewire::actingAs($this->staff)
        ->test(MenuManager::class)
        ->set('selectedOutletId', $foreignOutlet->id)
        ->assertForbidden();
});

test('creating a menu item under a category from a different outlet is forbidden', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    $foreignCategory = MenuCategory::factory()->create();

    Livewire::actingAs($this->staff)
        ->test(MenuManager::class)
        ->set('selectedOutletId', $outlet->id)
        ->call('createItem', $foreignCategory->id)
        ->assertForbidden();
});

test('selecting an outlet outside the branch is forbidden in the POS terminal', function (): void {
    $foreignOutlet = RestaurantOutlet::factory()->create();

    Livewire::actingAs($this->staff)
        ->test(PosTerminal::class)
        ->set('selectedOutletId', $foreignOutlet->id)
        ->assertForbidden();
});

test('starting a room-service order for a guest from a different tenant is forbidden', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    $foreignGuest = Guest::factory()->create();

    Livewire::actingAs($this->staff)
        ->test(PosTerminal::class)
        ->set('selectedOutletId', $outlet->id)
        ->call('startRoomServiceOrder', $foreignGuest->id)
        ->assertForbidden();
});

test('adding a menu item from a different outlet to the active order surfaces an error', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    $foreignCategory = MenuCategory::factory()->create();
    $foreignItem = MenuItem::factory()->create(['menu_category_id' => $foreignCategory->id]);

    $order = app(CreateRestaurantOrderAction::class)->handle($this->branch->id, $outlet->id, $this->staff, OrderType::Takeaway);

    Livewire::actingAs($this->staff)
        ->test(PosTerminal::class)
        ->set('selectedOutletId', $outlet->id)
        ->set('activeOrderId', $order->id)
        ->call('addItem', $foreignItem->id)
        ->assertHasErrors();

    expect($order->fresh()->items)->toBeEmpty();
});

test('selecting a free table starts a new dine-in order', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    $table = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Free]);

    Livewire::actingAs($this->staff)
        ->test(PosTerminal::class)
        ->set('selectedOutletId', $outlet->id)
        ->call('selectTable', $table->id)
        ->assertSet('activeOrderId', fn (?int $id) => $id !== null);

    expect(RestaurantOrder::where('table_id', $table->id)->count())->toBe(1);
});

test('selecting an occupied table resumes its existing order instead of creating a new one', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    $table = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Free]);
    $order = app(CreateRestaurantOrderAction::class)->handle($this->branch->id, $outlet->id, $this->staff, OrderType::DineIn, $table);

    Livewire::actingAs($this->staff)
        ->test(PosTerminal::class)
        ->set('selectedOutletId', $outlet->id)
        ->call('selectTable', $table->id)
        ->assertSet('activeOrderId', $order->id);

    expect(RestaurantOrder::where('table_id', $table->id)->count())->toBe(1);
});

test('room service guest search matches by room number, not just name', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);
    $roomType = RoomType::factory()->create(['branch_id' => $this->branch->id]);
    $room = Room::factory()->create(['branch_id' => $this->branch->id, 'room_type_id' => $roomType->id, 'room_number' => '204']);
    $guest = Guest::factory()->create(['tenant_id' => $this->branch->tenant_id, 'first_name' => 'Priya', 'last_name' => 'Shah']);
    $reservation = Reservation::factory()->checkedIn()->create(['branch_id' => $this->branch->id, 'guest_id' => $guest->id]);
    ReservationRoom::factory()->create(['reservation_id' => $reservation->id, 'room_type_id' => $roomType->id, 'room_id' => $room->id]);

    Livewire::actingAs($this->staff)
        ->test(PosTerminal::class)
        ->set('selectedOutletId', $outlet->id)
        ->set('guestSearch', '204')
        ->call('searchGuests')
        ->assertSee('Priya')
        ->assertSee('204')
        ->assertSee('1 guest found');
});

test('searching with no matches shows an explicit empty state', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->staff)
        ->test(PosTerminal::class)
        ->set('selectedOutletId', $outlet->id)
        ->set('guestSearch', 'Nonexistent Guest')
        ->call('searchGuests')
        ->assertSee('No guests match');
});

test('the empty state and result count are not shown before a search has been submitted', function (): void {
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->staff)
        ->test(PosTerminal::class)
        ->set('selectedOutletId', $outlet->id)
        ->set('guestSearch', 'Nonexistent Guest')
        ->assertDontSee('No guests match')
        ->assertDontSee('found');
});
