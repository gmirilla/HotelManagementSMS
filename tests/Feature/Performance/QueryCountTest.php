<?php

declare(strict_types=1);

/**
 * NFR-PERF-003: list endpoints must not N+1. Rather than asserting an
 * arbitrary query-count ceiling (which drifts and stops meaning anything),
 * each test renders the same view against a small dataset and a larger one
 * and asserts the query count stayed flat — that's what actually proves
 * eager loading is doing its job regardless of how many rows are rendered.
 *
 * Each pair of measurements is preceded by one unmeasured "warm-up" render.
 * Without it, the *first* measured call picks up Spatie's permission/role
 * cache being populated (several extra queries) while the second call gets
 * to reuse that already-warm cache — a purely test-methodology artifact
 * that has nothing to do with the eager loading actually being asserted,
 * and which produces a false N+1 failure if left in.
 */

use App\Domain\CRM\Actions\LogGuestFeedbackAction;
use App\Domain\CRM\Enums\FeedbackType;
use App\Domain\Restaurant\Actions\AddOrderItemAction;
use App\Domain\Restaurant\Actions\CloseRestaurantOrderAction;
use App\Domain\Restaurant\Actions\CreateRestaurantOrderAction;
use App\Domain\Restaurant\Enums\OrderType;
use App\Livewire\CRM\FeedbackManager;
use App\Livewire\Reservations\ReservationManager;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\InventoryItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemIngredient;
use App\Models\Reservation;
use App\Models\RestaurantOutlet;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('the reservations list does not N+1 as the number of reservations grows', function (): void {
    Permission::firstOrCreate(['name' => 'reservations.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'QueryCount Reservation Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.view');

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['tenant_id' => $branch->tenant_id, 'current_branch_id' => $branch->id]);
    $user->assignRole($role);
    $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    Reservation::factory()->count(2)->create(['branch_id' => $branch->id]);

    Livewire::actingAs($user)->test(ReservationManager::class); // warm-up
    $queriesForTwo = countQueries(fn () => Livewire::actingAs($user)->test(ReservationManager::class)->assertOk());

    Reservation::factory()->count(12)->create(['branch_id' => $branch->id]);
    $queriesForFourteen = countQueries(fn () => Livewire::actingAs($user)->test(ReservationManager::class)->assertOk());

    expect($queriesForFourteen)->toBe($queriesForTwo);
});

test('the guest feedback list does not N+1 as the number of feedback items grows', function (): void {
    Permission::firstOrCreate(['name' => 'crm.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'QueryCount Feedback Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('crm.manage');

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['tenant_id' => $branch->tenant_id, 'current_branch_id' => $branch->id]);
    $user->assignRole($role);
    $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    $logFeedback = app(LogGuestFeedbackAction::class);
    $guest = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);

    $logFeedback->handle($branch, $guest, FeedbackType::Complaint, 'Subject 1', 'Description 1');

    Livewire::actingAs($user)->test(FeedbackManager::class); // warm-up
    $queriesForOne = countQueries(fn () => Livewire::actingAs($user)->test(FeedbackManager::class)->assertOk());

    foreach (range(1, 10) as $i) {
        $logFeedback->handle($branch, $guest, FeedbackType::Complaint, "Subject {$i}", "Description {$i}");
    }
    $queriesForEleven = countQueries(fn () => Livewire::actingAs($user)->test(FeedbackManager::class)->assertOk());

    expect($queriesForEleven)->toBe($queriesForOne);
});

test('the reservations API index does not N+1 as the number of bookings grows', function (): void {
    Permission::firstOrCreate(['name' => 'reservations.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'QueryCount Api Reservation Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.view');

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['tenant_id' => $branch->tenant_id]);
    $user->assignRole($role);
    $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);
    $token = $user->createToken('test', ['bookings:read'])->plainTextToken;

    Reservation::factory()->count(2)->create(['branch_id' => $branch->id]);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson("/api/v1/reservations?branch_id={$branch->id}"); // warm-up
    $queriesForTwo = countQueries(fn () => $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/reservations?branch_id={$branch->id}")
        ->assertOk());

    Reservation::factory()->count(12)->create(['branch_id' => $branch->id]);
    $queriesForFourteen = countQueries(fn () => $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/reservations?branch_id={$branch->id}")
        ->assertOk());

    expect($queriesForFourteen)->toBe($queriesForTwo);
});

test('closing a restaurant order looks up menu-item ingredients in one batched query regardless of line count', function (): void {
    // Deliberately not asserting a flat *total* query count here: closing an
    // order legitimately issues one stock-movement write per distinct
    // ingredient (IssueStockAction), which scales with line count and isn't
    // an N+1 — the bug was the ingredient *lookup* being repeated per line
    // instead of batched, so that's what this test isolates and asserts.
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    $staff = User::factory()->create();

    $buildOrderWithLineCount = function (int $lineCount) use ($branch, $staff) {
        $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
        $outlet = RestaurantOutlet::factory()->create(['branch_id' => $branch->id]);
        $order = app(CreateRestaurantOrderAction::class)->handle($branch->id, $outlet->id, $staff, OrderType::Takeaway);

        foreach (range(1, $lineCount) as $i) {
            $ingredient = InventoryItem::factory()->withOpeningStock(100)->create(['warehouse_id' => $warehouse->id]);
            $category = MenuCategory::factory()->create(['outlet_id' => $outlet->id]);
            $menuItem = MenuItem::factory()->create(['menu_category_id' => $category->id, 'price_cents' => 1000]);
            MenuItemIngredient::create([
                'menu_item_id' => $menuItem->id,
                'inventory_item_id' => $ingredient->id,
                'quantity' => 0.1,
                'unit' => 'kg',
            ]);
            app(AddOrderItemAction::class)->handle($order->fresh(), $menuItem, 1);
        }

        return $order->fresh();
    };

    $countIngredientQueries = function ($order) use ($staff): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(CloseRestaurantOrderAction::class)->handle($order, $staff);
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        return collect($log)->filter(fn (array $entry) => str_contains($entry['query'], 'menu_item_ingredients'))->count();
    };

    $countIngredientQueries($buildOrderWithLineCount(2)); // warm-up

    expect($countIngredientQueries($buildOrderWithLineCount(2)))->toBe(1)
        ->and($countIngredientQueries($buildOrderWithLineCount(12)))->toBe(1);
});
