<?php

declare(strict_types=1);

use App\Domain\Restaurant\Actions\AddOrderItemAction;
use App\Domain\Restaurant\Actions\CloseRestaurantOrderAction;
use App\Domain\Restaurant\Actions\CreateRestaurantOrderAction;
use App\Domain\Restaurant\Actions\SendOrderToKitchenAction;
use App\Domain\Restaurant\Actions\UpdateKitchenItemStatusAction;
use App\Domain\Restaurant\Enums\KitchenStatus;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Enums\OrderType;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\InventoryItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemIngredient;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOutlet;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

function makeMenuItemWithIngredient(Branch $branch, float $ingredientQtyPerServing = 0.25): array
{
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $ingredient = InventoryItem::factory()->withOpeningStock(10)->create(['warehouse_id' => $warehouse->id]);
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $branch->id]);
    $category = MenuCategory::factory()->create(['outlet_id' => $outlet->id]);
    $menuItem = MenuItem::factory()->create(['menu_category_id' => $category->id, 'price_cents' => 2000]);
    MenuItemIngredient::create([
        'menu_item_id' => $menuItem->id,
        'inventory_item_id' => $ingredient->id,
        'quantity' => $ingredientQtyPerServing,
        'unit' => 'kg',
    ]);

    return [$outlet, $menuItem, $ingredient];
}

test('a dine-in order requires a free table', function (): void {
    $branch = Branch::factory()->create();
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $branch->id]);
    $table = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Occupied]);
    $staff = User::factory()->create();

    app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::DineIn, $table);
})->throws(ValidationException::class);

test('starting a dine-in order occupies the table', function (): void {
    $branch = Branch::factory()->create();
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $branch->id]);
    $table = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Free]);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::DineIn, $table);

    expect($order->status)->toBe(OrderStatus::Open)
        ->and($table->fresh()->status)->toBe(TableStatus::Occupied);
});

test('adding items recalculates the order total including tax', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 2);

    $order->refresh();
    // 2 * 2000 = 4000 subtotal; 8% tax = 320; total = 4320.
    expect($order->tax_cents)->toBe(320)
        ->and($order->total_cents)->toBe(4320);
});

test('an order cannot be sent to the kitchen empty', function (): void {
    $branch = Branch::factory()->create();
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $branch->id]);
    $staff = User::factory()->create();
    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);

    app(SendOrderToKitchenAction::class)->handle($order);
})->throws(ValidationException::class);

test('marking every item served moves a sent order to served', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();
    app(SendOrderToKitchenAction::class)->handle($order);

    $item = $order->items->first();
    app(UpdateKitchenItemStatusAction::class)->handle($item, KitchenStatus::Served);

    expect($order->fresh()->status)->toBe(OrderStatus::Served);
});

test('closing an order deducts ingredients from inventory', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem, $ingredient] = makeMenuItemWithIngredient($branch, ingredientQtyPerServing: 0.25);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 3);
    $order->refresh();

    app(CloseRestaurantOrderAction::class)->handle($order, $staff);

    // 3 servings * 0.25kg = 0.75kg, rounded up to 1 whole unit of issue.
    expect($ingredient->fresh()->quantity_on_hand)->toBe(9)
        ->and($order->fresh()->status)->toBe(OrderStatus::Closed);
});

test('closing a dine-in order frees its table', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $table = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Free]);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::DineIn, $table);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();

    app(CloseRestaurantOrderAction::class)->handle($order, $staff);

    expect($table->fresh()->status)->toBe(TableStatus::Free);
});

test('closing a room-service order posts the total to the guest open folio', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $guest = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);
    $folio = Folio::factory()->create(['branch_id' => $branch->id, 'guest_id' => $guest->id, 'balance_cents' => 0]);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::RoomService, null, $guest->id);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();

    app(CloseRestaurantOrderAction::class)->handle($order, $staff);

    expect($order->fresh()->folio_id)->toBe($folio->id)
        ->and($folio->fresh()->balance_cents)->toBe($order->fresh()->total_cents)
        ->and($folio->charges()->count())->toBe(1);
});

test('an empty order cannot be closed', function (): void {
    $branch = Branch::factory()->create();
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $branch->id]);
    $staff = User::factory()->create();
    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);

    app(CloseRestaurantOrderAction::class)->handle($order, $staff);
})->throws(ValidationException::class);

test('items cannot be added to an order that is not open', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();
    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    RestaurantOrder::where('id', $order->id)->update(['status' => OrderStatus::Closed]);

    app(AddOrderItemAction::class)->handle($order->fresh(), $menuItem, 1);
})->throws(ValidationException::class);
