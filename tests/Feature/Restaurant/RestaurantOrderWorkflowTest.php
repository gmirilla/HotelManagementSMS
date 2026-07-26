<?php

declare(strict_types=1);

use App\Domain\Accounting\Enums\TaxAppliesTo;
use App\Domain\Restaurant\Actions\AddOrderItemAction;
use App\Domain\Restaurant\Actions\CloseRestaurantOrderAction;
use App\Domain\Restaurant\Actions\CreateRestaurantOrderAction;
use App\Domain\Restaurant\Actions\SendOrderToKitchenAction;
use App\Domain\Restaurant\Actions\UpdateKitchenItemStatusAction;
use App\Domain\Restaurant\Actions\VoidRestaurantOrderAction;
use App\Domain\Restaurant\Enums\KitchenStatus;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Enums\OrderType;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\InventoryItem;
use App\Models\JournalEntry;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemIngredient;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOutlet;
use App\Models\RestaurantTable;
use App\Models\TaxRule;
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

test('adding items recalculates the order total using the branch\'s restaurant tax rule', function (): void {
    $branch = Branch::factory()->create();
    TaxRule::factory()->create(['branch_id' => $branch->id, 'applies_to' => TaxAppliesTo::Restaurant, 'rate_percent' => 8, 'is_active' => true]);
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 2);

    $order->refresh();
    // 2 * 2000 = 4000 subtotal; 8% tax = 320; total = 4320.
    expect($order->tax_cents)->toBe(320)
        ->and($order->total_cents)->toBe(4320);
});

test('a branch-wide tax rule applies when no restaurant-specific rule exists', function (): void {
    $branch = Branch::factory()->create();
    TaxRule::factory()->create(['branch_id' => $branch->id, 'applies_to' => TaxAppliesTo::All, 'rate_percent' => 5, 'is_active' => true]);
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);

    $order->refresh();
    // 1 * 2000 = 2000 subtotal; 5% tax = 100.
    expect($order->tax_cents)->toBe(100);
});

test('an inactive or missing tax rule leaves an order untaxed', function (): void {
    $branch = Branch::factory()->create();
    TaxRule::factory()->create(['branch_id' => $branch->id, 'applies_to' => TaxAppliesTo::Restaurant, 'rate_percent' => 8, 'is_active' => false]);
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);

    $order->refresh();
    expect($order->tax_cents)->toBe(0)
        ->and($order->total_cents)->toBe(2000);
});

test('a restaurant-specific tax rule wins over a branch-wide one', function (): void {
    $branch = Branch::factory()->create();
    TaxRule::factory()->create(['branch_id' => $branch->id, 'applies_to' => TaxAppliesTo::All, 'rate_percent' => 5, 'is_active' => true]);
    TaxRule::factory()->create(['branch_id' => $branch->id, 'applies_to' => TaxAppliesTo::Restaurant, 'rate_percent' => 8, 'is_active' => true]);
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);

    $order->refresh();
    // 1 * 2000 = 2000 subtotal; 8% (restaurant-specific) tax = 160, not 5%.
    expect($order->tax_cents)->toBe(160);
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
    app(UpdateKitchenItemStatusAction::class)->handle($item, KitchenStatus::Preparing);
    app(UpdateKitchenItemStatusAction::class)->handle($item->fresh(), KitchenStatus::Ready);
    app(UpdateKitchenItemStatusAction::class)->handle($item->fresh(), KitchenStatus::Served);

    expect($order->fresh()->status)->toBe(OrderStatus::Served);
});

test('a kitchen item cannot skip a step', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();
    app(SendOrderToKitchenAction::class)->handle($order);

    $item = $order->items->first();
    app(UpdateKitchenItemStatusAction::class)->handle($item, KitchenStatus::Served);
})->throws(ValidationException::class);

test('a kitchen item cannot move backward', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();
    app(SendOrderToKitchenAction::class)->handle($order);

    $item = $order->items->first();
    app(UpdateKitchenItemStatusAction::class)->handle($item, KitchenStatus::Preparing);

    app(UpdateKitchenItemStatusAction::class)->handle($item->fresh(), KitchenStatus::Queued);
})->throws(ValidationException::class);

test('re-requesting a kitchen item\'s current status is a harmless no-op', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();
    app(SendOrderToKitchenAction::class)->handle($order);

    $item = $order->items->first();
    app(UpdateKitchenItemStatusAction::class)->handle($item, KitchenStatus::Queued);

    expect($item->fresh()->kitchen_status)->toBe(KitchenStatus::Queued);
});

test('closing an order deducts ingredients from inventory', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
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
    seedChartOfAccounts($branch);
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $table = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Free]);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::DineIn, $table);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();

    app(CloseRestaurantOrderAction::class)->handle($order, $staff);

    expect($table->fresh()->status)->toBe(TableStatus::Free);
});

test('closing a walk-in order with no folio posts a direct cash sale to the ledger', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();

    app(CloseRestaurantOrderAction::class)->handle($order, $staff);

    expect($order->fresh()->folio_id)->toBeNull();

    $ledgerEntry = JournalEntry::where('reference_type', $order->getMorphClass())->where('reference_id', $order->id)->firstOrFail();
    expect($ledgerEntry->isBalanced())->toBeTrue()
        ->and($ledgerEntry->totalDebitCents())->toBe($order->fresh()->total_cents);
});

test('closing a room-service order posts the total to the guest open folio', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
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

test('voiding an order frees its table and records the reason', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $table = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Free]);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::DineIn, $table);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();

    app(VoidRestaurantOrderAction::class)->handle($order, 'Guest walked out');

    expect($order->fresh()->status)->toBe(OrderStatus::Voided)
        ->and($order->fresh()->void_reason)->toBe('Guest walked out')
        ->and($table->fresh()->status)->toBe(TableStatus::Free);
});

test('voiding an order requires a reason', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();

    app(VoidRestaurantOrderAction::class)->handle($order, '   ');
})->throws(ValidationException::class);

test('a closed order cannot be voided', function (): void {
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();
    app(CloseRestaurantOrderAction::class)->handle($order, $staff);

    app(VoidRestaurantOrderAction::class)->handle($order->fresh(), 'too late');
})->throws(ValidationException::class);

test('an already-voided order cannot be voided again', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    app(AddOrderItemAction::class)->handle($order, $menuItem, 1);
    $order->refresh();
    app(VoidRestaurantOrderAction::class)->handle($order, 'first void');

    app(VoidRestaurantOrderAction::class)->handle($order->fresh(), 'second void');
})->throws(ValidationException::class);

test('a reserved table can be seated, and a free-or-reserved-only guard still blocks an occupied one', function (): void {
    $branch = Branch::factory()->create();
    $outlet = RestaurantOutlet::factory()->create(['branch_id' => $branch->id]);
    $reservedTable = RestaurantTable::factory()->create(['outlet_id' => $outlet->id, 'status' => TableStatus::Reserved]);
    $staff = User::factory()->create();

    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::DineIn, $reservedTable);

    expect($order->status)->toBe(OrderStatus::Open)
        ->and($reservedTable->fresh()->status)->toBe(TableStatus::Occupied);
});

test('items cannot be added to an order that is not open', function (): void {
    $branch = Branch::factory()->create();
    [$outlet, $menuItem] = makeMenuItemWithIngredient($branch);
    $staff = User::factory()->create();
    $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);
    RestaurantOrder::where('id', $order->id)->update(['status' => OrderStatus::Closed]);

    app(AddOrderItemAction::class)->handle($order->fresh(), $menuItem, 1);
})->throws(ValidationException::class);
