<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Accounting\Actions\PostJournalEntryAction;
use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\ApStatus;
use App\Domain\Accounting\Enums\ArStatus;
use App\Domain\Accounting\Enums\CashbookEntryType;
use App\Domain\Accounting\Enums\JournalSide;
use App\Domain\Accounting\Enums\TaxAppliesTo;
use App\Domain\CRM\Actions\AssignGuestFeedbackAction;
use App\Domain\CRM\Actions\EarnLoyaltyPointsAction;
use App\Domain\CRM\Actions\LogGuestFeedbackAction;
use App\Domain\CRM\Actions\RedeemLoyaltyPointsAction;
use App\Domain\CRM\Actions\ResolveGuestFeedbackAction;
use App\Domain\CRM\Enums\CouponDiscountType;
use App\Domain\CRM\Enums\CouponScope;
use App\Domain\CRM\Enums\FeedbackType;
use App\Domain\CRM\Enums\MarketingCampaignChannel;
use App\Domain\CRM\Enums\MarketingCampaignStatus;
use App\Domain\Event\Actions\AddEventBookingItemAction;
use App\Domain\Event\Actions\ConfirmEventBookingAction;
use App\Domain\Event\Actions\CreateEventBookingAction;
use App\Domain\Event\Enums\EventServiceCategory;
use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domain\Housekeeping\Enums\HousekeepingTaskType;
use App\Domain\HR\Actions\ApproveLeaveRequestAction;
use App\Domain\HR\Actions\ProcessPayrollRunAction;
use App\Domain\HR\Actions\RecordManualAttendanceAction;
use App\Domain\HR\Actions\RejectLeaveRequestAction;
use App\Domain\HR\Actions\SubmitLeaveRequestAction;
use App\Domain\HR\Enums\AttendanceStatus;
use App\Domain\HR\Enums\DisciplinarySeverity;
use App\Domain\HR\Enums\EmployeeStatus;
use App\Domain\HR\Enums\EmploymentType;
use App\Domain\HR\Enums\PerformanceRating;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Maintenance\Enums\WorkOrderPriority;
use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Domain\Reservation\Actions\CreateReservationAction;
use App\Domain\Reservation\Enums\ReservationSource;
use App\Domain\Restaurant\Enums\KitchenStatus;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Enums\OrderType;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\Account;
use App\Models\Amenity;
use App\Models\ApEntry;
use App\Models\ArEntry;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CashbookEntry;
use App\Models\CorporateAccount;
use App\Models\Coupon;
use App\Models\DisciplinaryRecord;
use App\Models\Employee;
use App\Models\EventService;
use App\Models\EventSpace;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\GuestContact;
use App\Models\GuestDocument;
use App\Models\GuestNote;
use App\Models\HousekeepingTask;
use App\Models\InventoryItem;
use App\Models\JobOpening;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\LostFoundItem;
use App\Models\MaintenanceWorkOrder;
use App\Models\MarketingCampaign;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemIngredient;
use App\Models\Payment;
use App\Models\PerformanceReview;
use App\Models\PurchaseOrder;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOutlet;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Supplier;
use App\Models\TaxRule;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Builds a realistic, browsable demo dataset for one hotel group across two branches.
 * Depends on RolePermissionSeeder having already run (roles must exist before staff
 * users are assigned to them).
 */
class HotelDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Aurora Hotels',
            'slug' => 'aurora-hotels',
            'default_currency' => 'USD',
        ]);

        $branches = collect([
            ['name' => 'Aurora Downtown', 'city' => 'Chicago'],
            ['name' => 'Aurora Beachfront', 'city' => 'Miami'],
        ])->map(fn (array $attrs) => Branch::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $attrs['name'],
            'code' => Str::upper(Str::substr($attrs['city'], 0, 3)) . '-01',
            'city' => $attrs['city'],
            'country' => 'United States',
        ]));

        $amenities = collect(['Free Wi-Fi', 'Air Conditioning', 'Mini Bar', 'City View', 'Balcony', 'Room Service', 'Flat-Screen TV', 'Safe'])
            ->map(fn (string $name) => Amenity::firstOrCreate(['name' => $name], ['icon' => 'heroicon-o-check-circle']));

        $superAdminRole = Role::where('name', 'Super Administrator')->firstOrFail();

        $superAdmin = User::factory()->create([
            'name' => 'System Administrator',
            'email' => 'admin@aurorahotels.test',
            'tenant_id' => $tenant->id,
        ]);
        $superAdmin->assignRole($superAdminRole);

        $owner = User::factory()->create([
            'name' => 'Aurora Owner',
            'email' => 'owner@aurorahotels.test',
            'tenant_id' => $tenant->id,
        ]);
        $owner->assignRole('Hotel Owner');

        $generalManager = User::factory()->create([
            'name' => 'Aurora General Manager',
            'email' => 'gm@aurorahotels.test',
            'tenant_id' => $tenant->id,
        ]);
        $generalManager->assignRole('General Manager');

        $corporateAccount = CorporateAccount::factory()->create([
            'tenant_id' => $tenant->id,
            'company_name' => 'Globex Travel Partners',
        ]);

        $travelAgentAccount = CorporateAccount::factory()->travelAgent()->create([
            'tenant_id' => $tenant->id,
            'company_name' => 'Sunset Travel Agency',
        ]);

        $rooms = collect();
        $staffByBranch = collect();

        foreach ($branches as $branch) {
            $roomTypes = collect(['Standard', 'Deluxe', 'Executive Suite'])->map(function (string $name) use ($branch, $amenities) {
                $roomType = RoomType::factory()->create([
                    'branch_id' => $branch->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                ]);

                $roomType->amenities()->attach($amenities->random(3)->pluck('id'));

                return $roomType;
            });

            $branchManager = User::factory()->create([
                'name' => "{$branch->city} Branch Manager",
                'email' => Str::slug($branch->city) . '.manager@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $branchManager->assignRole('Branch Manager');
            $this->assignToBranch($branchManager, $branch, 'Branch Manager');

            $receptionist = User::factory()->create([
                'name' => "{$branch->city} Receptionist",
                'email' => Str::slug($branch->city) . '.receptionist@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $receptionist->assignRole('Receptionist');
            $this->assignToBranch($receptionist, $branch, 'Receptionist');

            $housekeepingSupervisor = User::factory()->create([
                'name' => "{$branch->city} Housekeeping Supervisor",
                'email' => Str::slug($branch->city) . '.housekeeping@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $housekeepingSupervisor->assignRole('Housekeeping Supervisor');
            $this->assignToBranch($housekeepingSupervisor, $branch, 'Housekeeping Supervisor');

            $housekeepingStaff = User::factory()->create([
                'name' => "{$branch->city} Housekeeper",
                'email' => Str::slug($branch->city) . '.housekeeper@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $housekeepingStaff->assignRole('Housekeeping Staff');
            $this->assignToBranch($housekeepingStaff, $branch, 'Housekeeping Staff');

            $maintenanceOfficer = User::factory()->create([
                'name' => "{$branch->city} Maintenance Officer",
                'email' => Str::slug($branch->city) . '.maintenance@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $maintenanceOfficer->assignRole('Maintenance Officer');
            $this->assignToBranch($maintenanceOfficer, $branch, 'Maintenance Officer');

            $restaurantManager = User::factory()->create([
                'name' => "{$branch->city} Restaurant Manager",
                'email' => Str::slug($branch->city) . '.restaurant@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $restaurantManager->assignRole('Restaurant Manager');
            $this->assignToBranch($restaurantManager, $branch, 'Restaurant Manager');

            $waiter = User::factory()->create([
                'name' => "{$branch->city} Waiter",
                'email' => Str::slug($branch->city) . '.waiter@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $waiter->assignRole('Waiter');
            $this->assignToBranch($waiter, $branch, 'Waiter');

            $chef = User::factory()->create([
                'name' => "{$branch->city} Chef",
                'email' => Str::slug($branch->city) . '.chef@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $chef->assignRole('Chef');
            $this->assignToBranch($chef, $branch, 'Chef');

            $accountant = User::factory()->create([
                'name' => "{$branch->city} Accountant",
                'email' => Str::slug($branch->city) . '.accountant@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $accountant->assignRole('Accountant');
            $this->assignToBranch($accountant, $branch, 'Accountant');

            $cashier = User::factory()->create([
                'name' => "{$branch->city} Cashier",
                'email' => Str::slug($branch->city) . '.cashier@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $cashier->assignRole('Cashier');
            $this->assignToBranch($cashier, $branch, 'Cashier');

            $hrOfficer = User::factory()->create([
                'name' => "{$branch->city} HR Officer",
                'email' => Str::slug($branch->city) . '.hr@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $hrOfficer->assignRole('HR');
            $this->assignToBranch($hrOfficer, $branch, 'HR');

            $staffByBranch->put($branch->id, [
                'housekeepingSupervisor' => $housekeepingSupervisor,
                'housekeepingStaff' => $housekeepingStaff,
                'maintenanceOfficer' => $maintenanceOfficer,
                'restaurantManager' => $restaurantManager,
                'waiter' => $waiter,
                'chef' => $chef,
                'accountant' => $accountant,
                'cashier' => $cashier,
                'hrOfficer' => $hrOfficer,
                'branchManager' => $branchManager,
                'receptionist' => $receptionist,
            ]);

            foreach ($roomTypes as $roomType) {
                $branchRooms = Room::factory(5)->create([
                    'branch_id' => $branch->id,
                    'room_type_id' => $roomType->id,
                ]);

                $rooms = $rooms->merge($branchRooms);
            }
        }

        $guests = Guest::factory(25)->create(['tenant_id' => $tenant->id]);

        $guests->take(3)->each(fn (Guest $guest) => GuestDocument::factory()->create(['guest_id' => $guest->id]));
        $guests->take(3)->each(fn (Guest $guest) => GuestContact::factory()->create(['guest_id' => $guest->id]));

        $vipGuest = Guest::factory()->vip()->create(['tenant_id' => $tenant->id]);
        GuestNote::factory()->alert()->create(['guest_id' => $vipGuest->id, 'created_by_user_id' => $superAdmin->id]);

        Guest::factory()->blacklisted()->create(['tenant_id' => $tenant->id]);

        $roomsByBranch = $rooms->groupBy('branch_id');

        // Past, settled stays.
        $branches->each(function (Branch $branch) use ($guests, $roomsByBranch, $corporateAccount) {
            foreach (range(1, 5) as $i) {
                $this->createSettledStay($branch, $guests->random(), $roomsByBranch->get($branch->id)->random(), $i % 4 === 0 ? $corporateAccount : null);
            }
        });

        // Guests currently in-house.
        $branches->each(function (Branch $branch) use ($guests, $roomsByBranch) {
            foreach (range(1, 3) as $i) {
                $this->createInHouseStay($branch, $guests->random(), $roomsByBranch->get($branch->id)->random());
            }
        });

        // Upcoming, confirmed reservations — routed through
        // CreateReservationAction (not a bare factory) so each one gets a
        // real reservation_rooms row with a room type attached. A bare
        // Reservation::factory()->create() has no room type at all, which
        // front desk can't check in (CheckInGuestAction requires an
        // existing reservation-room); with a randomized arrival_date that
        // silently produced "arrivals today" the front desk could never
        // actually seat. The first offset (0 days) guarantees a real,
        // checkable-in arrival for today; the rest are staggered so they
        // never compete with each other or the in-house stays above for the
        // same room type's 5-room inventory.
        $branches->each(function (Branch $branch) use ($guests) {
            $roomTypes = RoomType::where('branch_id', $branch->id)->orderBy('id')->get();

            foreach ([0, 2, 4, 6, 8, 10] as $i => $offsetDays) {
                $roomType = $roomTypes[$i % $roomTypes->count()];

                app(CreateReservationAction::class)->handle(
                    branchId: $branch->id,
                    guestId: $guests->random()->id,
                    roomType: $roomType,
                    arrival: now()->addDays($offsetDays)->startOfDay(),
                    departure: now()->addDays($offsetDays + 2)->startOfDay(),
                    adults: fake()->numberBetween(1, 2),
                    children: 0,
                    source: ReservationSource::Online->value,
                );
            }
        });

        // Cancelled reservation.
        $branches->each(function (Branch $branch) use ($guests) {
            Reservation::factory()->cancelled()->create([
                'branch_id' => $branch->id,
                'guest_id' => $guests->random()->id,
            ]);
        });

        // Waitlist entry.
        $firstBranch = $branches->first();
        WaitlistEntry::factory()->create([
            'branch_id' => $firstBranch->id,
            'guest_id' => $guests->random()->id,
            'room_type_id' => RoomType::where('branch_id', $firstBranch->id)->first()->id,
        ]);

        $branches->each(function (Branch $branch) use ($roomsByBranch, $staffByBranch) {
            $this->seedHousekeepingAndMaintenance($branch, $roomsByBranch->get($branch->id), $staffByBranch->get($branch->id));
        });

        $procurementByBranch = collect();
        $branches->each(function (Branch $branch) use ($tenant, $staffByBranch, $guests, $procurementByBranch) {
            $procurementByBranch->put(
                $branch->id,
                $this->seedInventoryAndRestaurant($branch, $tenant, $staffByBranch->get($branch->id), $guests),
            );
        });

        $branches->each(function (Branch $branch) use ($staffByBranch, $procurementByBranch, $corporateAccount) {
            $procurement = $procurementByBranch->get($branch->id);

            $this->seedAccounting(
                $branch,
                $staffByBranch->get($branch->id)['accountant'],
                $corporateAccount,
                $procurement['supplier'],
                $procurement['purchaseOrder'],
            );
        });

        $branches->each(function (Branch $branch) use ($staffByBranch) {
            $this->seedHR($branch, $staffByBranch->get($branch->id));
        });

        $branches->each(function (Branch $branch) use ($staffByBranch, $guests, $corporateAccount, $travelAgentAccount) {
            $this->seedCRMAndEvents($branch, $staffByBranch->get($branch->id), $guests, $corporateAccount, $travelAgentAccount);
        });
    }

    /**
     * @param  Collection<int, Room>  $branchRooms
     * @param  array{housekeepingSupervisor: User, housekeepingStaff: User, maintenanceOfficer: User}  $staff
     */
    private function seedHousekeepingAndMaintenance(Branch $branch, $branchRooms, array $staff): void
    {
        // A handful of today's checkout-clean tasks, at various points in the workflow.
        $roomsNeedingCleaning = $branchRooms->random(4);

        HousekeepingTask::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $roomsNeedingCleaning[0]->id,
            'task_type' => HousekeepingTaskType::CheckoutClean,
            'status' => HousekeepingTaskStatus::Pending,
        ]);

        HousekeepingTask::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $roomsNeedingCleaning[1]->id,
            'task_type' => HousekeepingTaskType::StayoverClean,
            'status' => HousekeepingTaskStatus::InProgress,
            'assigned_to_user_id' => $staff['housekeepingStaff']->id,
            'started_at' => now()->subMinutes(20),
        ]);

        HousekeepingTask::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $roomsNeedingCleaning[2]->id,
            'task_type' => HousekeepingTaskType::CheckoutClean,
            'status' => HousekeepingTaskStatus::AwaitingInspection,
            'assigned_to_user_id' => $staff['housekeepingStaff']->id,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(10),
        ]);

        HousekeepingTask::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $roomsNeedingCleaning[3]->id,
            'task_type' => HousekeepingTaskType::CheckoutClean,
            'status' => HousekeepingTaskStatus::Completed,
            'assigned_to_user_id' => $staff['housekeepingStaff']->id,
            'inspected_by_user_id' => $staff['housekeepingSupervisor']->id,
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(2),
        ]);

        LostFoundItem::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $roomsNeedingCleaning[0]->id,
            'found_by_user_id' => $staff['housekeepingStaff']->id,
        ]);

        // Assets and a mix of open/completed/verified work orders.
        $assets = Asset::factory(2)->create(['branch_id' => $branch->id]);

        MaintenanceWorkOrder::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $branchRooms->random()->id,
            'reported_by_user_id' => $staff['housekeepingStaff']->id,
            'assigned_to_user_id' => $staff['maintenanceOfficer']->id,
            'priority' => WorkOrderPriority::High,
            'status' => WorkOrderStatus::Open,
            'description' => 'Air conditioning unit not cooling.',
        ]);

        MaintenanceWorkOrder::factory()->preventive()->create([
            'branch_id' => $branch->id,
            'asset_id' => $assets->first()->id,
            'reported_by_user_id' => $staff['maintenanceOfficer']->id,
            'assigned_to_user_id' => $staff['maintenanceOfficer']->id,
            'priority' => WorkOrderPriority::Low,
            'status' => WorkOrderStatus::Completed,
            'description' => 'Quarterly preventive service.',
            'parts_cost_cents' => 4500,
            'labor_cost_cents' => 8000,
            'completed_at' => now()->subDay(),
        ]);
    }

    /**
     * @param  array{restaurantManager: User, waiter: User, chef: User}  $staff
     * @param  Collection<int, Guest>  $guests
     *
     * @return array{supplier: Supplier, purchaseOrder: PurchaseOrder}
     */
    private function seedInventoryAndRestaurant(Branch $branch, Tenant $tenant, array $staff, Collection $guests): array
    {
        $warehouse = Warehouse::create(['branch_id' => $branch->id, 'name' => 'Main Store', 'type' => 'main_store']);

        $itemSpecs = [
            ['name' => 'Bath Towels', 'unit' => 'unit', 'qty' => 150, 'reorder' => 40, 'cost' => 800],
            ['name' => 'Bottled Water', 'unit' => 'unit', 'qty' => 12, 'reorder' => 50, 'cost' => 50],
            ['name' => 'Coffee Beans', 'unit' => 'kg', 'qty' => 25, 'reorder' => 10, 'cost' => 1800],
            ['name' => 'Fresh Salmon', 'unit' => 'kg', 'qty' => 8, 'reorder' => 5, 'cost' => 2200, 'perishable' => true],
            ['name' => 'Toilet Paper', 'unit' => 'box', 'qty' => 30, 'reorder' => 15, 'cost' => 1200],
            ['name' => 'Chicken Breast', 'unit' => 'kg', 'qty' => 15, 'reorder' => 8, 'cost' => 900, 'perishable' => true],
        ];

        $items = collect($itemSpecs)->map(function (array $spec) use ($warehouse) {
            $item = InventoryItem::create([
                'warehouse_id' => $warehouse->id,
                'sku' => Str::upper(Str::slug($spec['name'])) . '-' . $warehouse->id,
                'name' => $spec['name'],
                'unit_of_measure' => $spec['unit'],
                'reorder_point' => $spec['reorder'],
                'quantity_on_hand' => $spec['qty'],
                'average_cost_cents' => $spec['cost'],
                'is_perishable' => $spec['perishable'] ?? false,
                'expires_on' => ($spec['perishable'] ?? false) ? now()->addDays(5) : null,
            ]);

            $item->stockMovements()->create([
                'movement_type' => StockMovementType::Receipt,
                'quantity' => $spec['qty'],
                'unit_cost_cents' => $spec['cost'],
                'notes' => 'Opening stock',
            ]);

            return $item;
        })->keyBy('name');

        // An outstanding purchase order awaiting delivery, to demo the goods-receipt flow.
        $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Metro Foodservice Supply']);
        $po = PurchaseOrder::create([
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'created_by_user_id' => $staff['restaurantManager']->id,
            'po_number' => 'PO-' . Str::upper(Str::random(6)),
            'status' => PurchaseOrderStatus::Sent,
        ]);
        $poItem1 = $po->items()->create([
            'inventory_item_id' => $items['Bottled Water']->id,
            'quantity_ordered' => 200,
            'unit_cost_cents' => 45,
        ]);
        $poItem2 = $po->items()->create([
            'inventory_item_id' => $items['Fresh Salmon']->id,
            'quantity_ordered' => 20,
            'unit_cost_cents' => 2100,
        ]);
        $po->update(['total_cents' => $poItem1->quantity_ordered * $poItem1->unit_cost_cents + $poItem2->quantity_ordered * $poItem2->unit_cost_cents]);

        // Restaurant outlet, tables, and a menu with ingredient links.
        $outlet = RestaurantOutlet::create(['branch_id' => $branch->id, 'name' => 'The Grand Dining Room', 'outlet_type' => 'restaurant']);

        $tables = collect([
            ['label' => '1', 'seats' => 2, 'status' => TableStatus::Occupied],
            ['label' => '2', 'seats' => 4, 'status' => TableStatus::Free],
            ['label' => '3', 'seats' => 4, 'status' => TableStatus::Reserved],
            ['label' => '4', 'seats' => 6, 'status' => TableStatus::Free],
        ])->map(fn (array $t) => RestaurantTable::create(['outlet_id' => $outlet->id, ...$t]));

        $mains = MenuCategory::create(['outlet_id' => $outlet->id, 'name' => 'Main Courses', 'display_order' => 1]);
        $beverages = MenuCategory::create(['outlet_id' => $outlet->id, 'name' => 'Beverages', 'display_order' => 2]);

        $salmonDish = MenuItem::create(['menu_category_id' => $mains->id, 'name' => 'Grilled Salmon', 'price_cents' => 3200]);
        MenuItemIngredient::create(['menu_item_id' => $salmonDish->id, 'inventory_item_id' => $items['Fresh Salmon']->id, 'quantity' => 0.25, 'unit' => 'kg']);

        $chickenDish = MenuItem::create(['menu_category_id' => $mains->id, 'name' => 'Herb Roasted Chicken', 'price_cents' => 2600]);
        MenuItemIngredient::create(['menu_item_id' => $chickenDish->id, 'inventory_item_id' => $items['Chicken Breast']->id, 'quantity' => 0.3, 'unit' => 'kg']);

        $coffee = MenuItem::create(['menu_category_id' => $beverages->id, 'name' => 'Coffee', 'price_cents' => 500]);
        MenuItemIngredient::create(['menu_item_id' => $coffee->id, 'inventory_item_id' => $items['Coffee Beans']->id, 'quantity' => 0.02, 'unit' => 'kg']);

        MenuItem::create(['menu_category_id' => $beverages->id, 'name' => 'Bottled Water', 'price_cents' => 400]);

        // An open dine-in order at the occupied table.
        $openOrder = RestaurantOrder::create([
            'branch_id' => $branch->id,
            'outlet_id' => $outlet->id,
            'table_id' => $tables->first()->id,
            'order_type' => OrderType::DineIn,
            'status' => OrderStatus::SentToKitchen,
            'opened_by_user_id' => $staff['waiter']->id,
        ]);
        $openOrder->items()->create([
            'menu_item_id' => $salmonDish->id,
            'quantity' => 1,
            'unit_price_cents' => $salmonDish->price_cents,
            'kitchen_status' => KitchenStatus::Preparing,
        ]);
        $openOrder->items()->create([
            'menu_item_id' => $coffee->id,
            'quantity' => 2,
            'unit_price_cents' => $coffee->price_cents,
            'kitchen_status' => KitchenStatus::Queued,
        ]);
        $subtotal = $salmonDish->price_cents + (2 * $coffee->price_cents);
        $openOrder->update(['tax_cents' => (int) round($subtotal * 0.08), 'total_cents' => $subtotal + (int) round($subtotal * 0.08)]);

        // A settled room-service order for the sales history.
        RestaurantOrder::create([
            'branch_id' => $branch->id,
            'outlet_id' => $outlet->id,
            'guest_id' => $guests->random()->id,
            'order_type' => OrderType::RoomService,
            'status' => OrderStatus::Closed,
            'opened_by_user_id' => $staff['waiter']->id,
            'tax_cents' => (int) round($chickenDish->price_cents * 0.08),
            'total_cents' => $chickenDish->price_cents + (int) round($chickenDish->price_cents * 0.08),
        ])->items()->create([
            'menu_item_id' => $chickenDish->id,
            'quantity' => 1,
            'unit_price_cents' => $chickenDish->price_cents,
            'kitchen_status' => KitchenStatus::Served,
        ]);

        return ['supplier' => $supplier, 'purchaseOrder' => $po];
    }

    /**
     * Builds a representative chart of accounts and demonstrates the
     * double-entry posting flow (PostJournalEntryAction), plus AR/AP/cashbook
     * and tax-rule demo data, so the Accounting module has something to browse.
     */
    private function seedAccounting(
        Branch $branch,
        User $accountant,
        ?CorporateAccount $corporateAccount,
        Supplier $supplier,
        PurchaseOrder $purchaseOrder,
    ): void {
        $accounts = collect([
            ['code' => '1000', 'name' => 'Cash', 'type' => AccountType::Asset],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => AccountType::Asset],
            ['code' => '1200', 'name' => 'Inventory', 'type' => AccountType::Asset],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => AccountType::Liability],
            ['code' => '2100', 'name' => 'Taxes Payable', 'type' => AccountType::Liability],
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => AccountType::Equity],
            ['code' => '4000', 'name' => 'Room Revenue', 'type' => AccountType::Revenue],
            ['code' => '4100', 'name' => 'Restaurant Revenue', 'type' => AccountType::Revenue],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => AccountType::Expense],
            ['code' => '5100', 'name' => 'Payroll Expense', 'type' => AccountType::Expense],
            ['code' => '5200', 'name' => 'Utilities Expense', 'type' => AccountType::Expense],
            ['code' => '5300', 'name' => 'Maintenance Expense', 'type' => AccountType::Expense],
        ])->map(fn (array $spec) => Account::create([
            'branch_id' => $branch->id,
            'code' => $spec['code'],
            'name' => $spec['name'],
            'account_type' => $spec['type'],
        ]))->keyBy('code');

        $postJournalEntry = app(PostJournalEntryAction::class);

        $postJournalEntry->handle(
            branchId: $branch->id,
            entryDate: now()->subDays(7),
            lines: [
                ['account_id' => $accounts['1000']->id, 'side' => JournalSide::Debit, 'amount_cents' => 45000],
                ['account_id' => $accounts['4000']->id, 'side' => JournalSide::Credit, 'amount_cents' => 45000],
            ],
            memo: 'Cash payment for guest folio settlement',
            createdBy: $accountant,
        );

        $postJournalEntry->handle(
            branchId: $branch->id,
            entryDate: now()->subDays(5),
            lines: [
                ['account_id' => $accounts['1100']->id, 'side' => JournalSide::Debit, 'amount_cents' => 168000],
                ['account_id' => $accounts['4000']->id, 'side' => JournalSide::Credit, 'amount_cents' => 150000],
                ['account_id' => $accounts['2100']->id, 'side' => JournalSide::Credit, 'amount_cents' => 18000],
            ],
            memo: 'Corporate account billed stay — Globex Travel Partners',
            createdBy: $accountant,
        );

        $postJournalEntry->handle(
            branchId: $branch->id,
            entryDate: now()->subDays(3),
            lines: [
                ['account_id' => $accounts['5200']->id, 'side' => JournalSide::Debit, 'amount_cents' => 32000],
                ['account_id' => $accounts['2000']->id, 'side' => JournalSide::Credit, 'amount_cents' => 32000],
            ],
            memo: 'Monthly utilities invoice',
            createdBy: $accountant,
        );

        $postJournalEntry->handle(
            branchId: $branch->id,
            entryDate: now()->subDays(1),
            lines: [
                ['account_id' => $accounts['5100']->id, 'side' => JournalSide::Debit, 'amount_cents' => 520000],
                ['account_id' => $accounts['1000']->id, 'side' => JournalSide::Credit, 'amount_cents' => 520000],
            ],
            memo: 'Bi-weekly payroll run',
            createdBy: $accountant,
        );

        if ($corporateAccount instanceof CorporateAccount) {
            ArEntry::create([
                'branch_id' => $branch->id,
                'corporate_account_id' => $corporateAccount->id,
                'amount_cents' => 168000,
                'paid_cents' => 0,
                'due_date' => now()->addDays(23),
                'status' => ArStatus::Open,
            ]);

            ArEntry::create([
                'branch_id' => $branch->id,
                'corporate_account_id' => $corporateAccount->id,
                'amount_cents' => 92000,
                'paid_cents' => 92000,
                'due_date' => now()->subDays(10),
                'status' => ArStatus::Paid,
            ]);
        }

        ApEntry::create([
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrder->id,
            'amount_cents' => $purchaseOrder->total_cents,
            'paid_cents' => 0,
            'due_date' => now()->addDays(14),
            'status' => ApStatus::Open,
        ]);

        ApEntry::create([
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'amount_cents' => 36000,
            'paid_cents' => 18000,
            'due_date' => now()->addDays(2),
            'status' => ApStatus::PartiallyPaid,
        ]);

        foreach (range(1, 3) as $i) {
            CashbookEntry::create([
                'branch_id' => $branch->id,
                'cashier_user_id' => $accountant->id,
                'entry_type' => CashbookEntryType::CashIn,
                'amount_cents' => 15000 * $i,
                'reason' => 'Front desk cash drawer deposit',
                'shift_date' => now()->subDays($i)->toDateString(),
                'reconciled' => $i > 1,
            ]);
        }

        CashbookEntry::create([
            'branch_id' => $branch->id,
            'cashier_user_id' => $accountant->id,
            'entry_type' => CashbookEntryType::CashOut,
            'amount_cents' => 5000,
            'reason' => 'Petty cash — office supplies',
            'shift_date' => now()->toDateString(),
            'reconciled' => false,
        ]);

        TaxRule::create(['branch_id' => $branch->id, 'name' => 'Occupancy Tax', 'rate_percent' => 12.00, 'applies_to' => TaxAppliesTo::Room, 'is_active' => true]);
        TaxRule::create(['branch_id' => $branch->id, 'name' => 'Restaurant Sales Tax', 'rate_percent' => 8.00, 'applies_to' => TaxAppliesTo::Restaurant, 'is_active' => true]);
    }

    /**
     * @param  array{branchManager: User, receptionist: User, housekeepingStaff: User, waiter: User, chef: User, hrOfficer: User}  $staff
     */
    private function seedHR(Branch $branch, array $staff): void
    {
        $employeeSpecs = [
            ['user' => $staff['branchManager'], 'department' => 'Management', 'job_title' => 'Branch Manager', 'salary' => 650000],
            ['user' => $staff['receptionist'], 'department' => 'Front Office', 'job_title' => 'Receptionist', 'salary' => 320000],
            ['user' => $staff['housekeepingStaff'], 'department' => 'Housekeeping', 'job_title' => 'Housekeeper', 'salary' => 280000],
            ['user' => $staff['waiter'], 'department' => 'F&B', 'job_title' => 'Waiter', 'salary' => 290000],
            ['user' => $staff['chef'], 'department' => 'F&B', 'job_title' => 'Chef', 'salary' => 420000],
            ['user' => $staff['hrOfficer'], 'department' => 'HR', 'job_title' => 'HR Officer', 'salary' => 380000],
            ['user' => null, 'department' => 'Front Office', 'job_title' => 'Night Auditor', 'salary' => 300000],
        ];

        $employees = collect($employeeSpecs)->map(fn (array $spec, int $index) => Employee::create([
            'branch_id' => $branch->id,
            'user_id' => $spec['user']?->id,
            'employee_number' => 'EMP-' . $branch->code . '-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'department' => $spec['department'],
            'job_title' => $spec['job_title'],
            'employment_type' => EmploymentType::FullTime,
            'status' => EmployeeStatus::Active,
            'hire_date' => now()->subYears(random_int(1, 4))->subDays(random_int(0, 200)),
            'base_salary_cents' => $spec['salary'],
            'email' => $spec['user']?->email,
        ]));

        $leaveTypes = collect([
            ['name' => 'Annual Leave', 'days' => 21, 'paid' => true],
            ['name' => 'Sick Leave', 'days' => 10, 'paid' => true],
            ['name' => 'Unpaid Leave', 'days' => 30, 'paid' => false],
        ])->map(fn (array $spec) => LeaveType::create([
            'branch_id' => $branch->id,
            'name' => $spec['name'],
            'days_per_year' => $spec['days'],
            'is_paid' => $spec['paid'],
        ]));

        $year = (int) now()->format('Y');
        $employees->each(function (Employee $employee) use ($leaveTypes, $year) {
            $leaveTypes->each(fn (LeaveType $leaveType) => LeaveBalance::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
                'entitled_days' => $leaveType->days_per_year,
            ]));
        });

        $annualLeave = $leaveTypes->firstWhere('name', 'Annual Leave');
        $sickLeave = $leaveTypes->firstWhere('name', 'Sick Leave');

        $submitLeaveRequest = app(SubmitLeaveRequestAction::class);

        $approvedRequest = $submitLeaveRequest->handle($employees[1], $annualLeave, now()->addDays(14), now()->addDays(16), 'Family trip');
        app(ApproveLeaveRequestAction::class)->handle($approvedRequest, $staff['hrOfficer']);

        $rejectedRequest = $submitLeaveRequest->handle($employees[3], $sickLeave, now()->addDays(2), now()->addDays(2), 'Feeling unwell');
        app(RejectLeaveRequestAction::class)->handle($rejectedRequest, $staff['hrOfficer'], 'Insufficient notice for the shift already scheduled');

        $submitLeaveRequest->handle($employees[2], $annualLeave, now()->addDays(30), now()->addDays(32), 'Personal time off');

        // A week of attendance history so the payroll run below has a ledger to compute from.
        $recordAttendance = app(RecordManualAttendanceAction::class);

        foreach ($employees as $employee) {
            for ($daysAgo = 7; $daysAgo >= 1; $daysAgo--) {
                $date = now()->subDays($daysAgo);

                if ($date->isWeekend()) {
                    continue;
                }

                $status = $daysAgo === 4 && $employee->is($employees[3]) ? AttendanceStatus::Absent : AttendanceStatus::Present;
                $recordAttendance->handle($employee, $date, $status);
            }
        }

        app(ProcessPayrollRunAction::class)->handle(
            $branch->id,
            now()->subMonthNoOverflow()->startOfMonth(),
            now()->subMonthNoOverflow()->endOfMonth(),
            $staff['hrOfficer'],
        );

        PerformanceReview::create([
            'employee_id' => $employees[1]->id,
            'reviewer_user_id' => $staff['branchManager']->id,
            'review_period' => $year . ' H1',
            'review_date' => now()->subDays(20)->toDateString(),
            'rating' => PerformanceRating::ExceedsExpectations,
            'strengths' => 'Consistently positive guest feedback and fast check-in times.',
            'areas_for_improvement' => 'Could delegate more during peak check-in rushes.',
            'comments' => 'Strong candidate for a front office supervisor track.',
        ]);

        DisciplinaryRecord::create([
            'employee_id' => $employees[3]->id,
            'reported_by_user_id' => $staff['hrOfficer']->id,
            'incident_date' => now()->subDays(10)->toDateString(),
            'severity' => DisciplinarySeverity::VerbalWarning,
            'description' => 'Arrived 40 minutes late for an assigned shift without prior notice.',
            'action_taken' => 'Verbal warning issued; reminded of the shift-notice policy.',
        ]);

        $opening = JobOpening::create([
            'branch_id' => $branch->id,
            'title' => 'Night Auditor',
            'department' => 'Front Office',
            'description' => 'Overnight front-desk coverage: audits, guest arrivals, and security rounds.',
        ]);

        Candidate::create(['job_opening_id' => $opening->id, 'name' => 'Priya Nair', 'email' => 'priya.nair@example.test', 'stage' => 'interview']);
        Candidate::create(['job_opening_id' => $opening->id, 'name' => 'Marcus Webb', 'email' => 'marcus.webb@example.test', 'stage' => 'screening']);
        Candidate::create(['job_opening_id' => $opening->id, 'name' => 'Elena Popescu', 'email' => 'elena.popescu@example.test', 'stage' => 'applied']);
    }

    /**
     * @param  array{branchManager: User, receptionist: User}  $staff
     * @param  Collection<int, Guest>  $guests
     */
    private function seedCRMAndEvents(Branch $branch, array $staff, Collection $guests, CorporateAccount $corporateAccount, CorporateAccount $travelAgentAccount): void
    {
        // Guest feedback workflow: one open, one in-progress (assigned), one resolved.
        $logFeedback = app(LogGuestFeedbackAction::class);

        $logFeedback->handle($branch, $guests->random(), FeedbackType::Suggestion, 'More vegetarian breakfast options', 'A guest suggested adding more plant-based items to the breakfast buffet.');

        $inProgressFeedback = $logFeedback->handle($branch, $guests->random(), FeedbackType::Complaint, 'Noisy air conditioning unit', 'Guest reported the AC unit in their room was unusually loud overnight.');
        app(AssignGuestFeedbackAction::class)->handle($inProgressFeedback, $staff['receptionist']);

        $resolvedFeedback = $logFeedback->handle($branch, $guests->random(), FeedbackType::Complaint, 'Late room service delivery', 'Room service took over an hour to arrive.');
        app(AssignGuestFeedbackAction::class)->handle($resolvedFeedback, $staff['receptionist']);
        app(ResolveGuestFeedbackAction::class)->handle($resolvedFeedback, 'Apologized to the guest and comped the order; kitchen staffing was adjusted for peak hours.');

        // Loyalty: three guests demonstrating the three tiers.
        $earnPoints = app(EarnLoyaltyPointsAction::class);
        $redeemPoints = app(RedeemLoyaltyPointsAction::class);

        $silverGuest = $guests[0];
        $goldGuest = $guests[1];
        $platinumGuest = $guests[2];

        $earnPoints->handle($silverGuest, 450, 'Points earned on recent stay');
        $earnPoints->handle($goldGuest, 6200, 'Points earned across multiple stays');
        $earnPoints->handle($platinumGuest, 16500, 'Points earned as a long-time repeat guest');
        $redeemPoints->handle($platinumGuest->loyaltyAccount()->first(), 2000, 'Redeemed for a complimentary room upgrade');

        // Coupons: one active, one expired.
        Coupon::create([
            'branch_id' => $branch->id,
            'code' => 'WELCOME10',
            'name' => 'Welcome discount',
            'discount_type' => CouponDiscountType::Percent,
            'discount_value' => 10,
            'scope' => CouponScope::All,
            'valid_from' => now()->subDays(10)->toDateString(),
            'valid_until' => now()->addMonths(3)->toDateString(),
            'usage_limit' => 100,
        ]);

        Coupon::create([
            'branch_id' => $branch->id,
            'code' => 'SUMMERFEST',
            'name' => 'Summer restaurant promotion',
            'discount_type' => CouponDiscountType::Fixed,
            'discount_value' => 1500,
            'scope' => CouponScope::Restaurant,
            'valid_from' => now()->subMonths(2)->toDateString(),
            'valid_until' => now()->subDays(5)->toDateString(),
            'is_active' => false,
        ]);

        MarketingCampaign::create([
            'branch_id' => $branch->id,
            'name' => 'Platinum tier exclusive offer',
            'channel' => MarketingCampaignChannel::Email,
            'segment_criteria' => ['loyalty_tier' => 'platinum'],
            'message' => 'Enjoy a complimentary spa treatment on your next stay with us.',
            'status' => MarketingCampaignStatus::Sent,
            'sent_at' => now()->subDays(3),
        ]);

        // Event spaces, services, and two bookings — one confirmed with a
        // consolidated bill, one still tentative.
        $ballroom = EventSpace::create([
            'branch_id' => $branch->id,
            'name' => 'Grand Ballroom',
            'capacity' => 250,
            'hourly_rate_cents' => 45000,
        ]);

        EventSpace::create([
            'branch_id' => $branch->id,
            'name' => 'Executive Boardroom',
            'capacity' => 20,
            'hourly_rate_cents' => 15000,
        ]);

        $cateringService = EventService::create([
            'branch_id' => $branch->id,
            'name' => 'Buffet Lunch',
            'category' => EventServiceCategory::Catering,
            'unit_price_cents' => 3500,
            'unit' => 'per_person',
        ]);

        $avService = EventService::create([
            'branch_id' => $branch->id,
            'name' => 'Projector & Screen',
            'category' => EventServiceCategory::Equipment,
            'unit_price_cents' => 15000,
            'unit' => 'flat',
        ]);

        $createBooking = app(CreateEventBookingAction::class);
        $addItem = app(AddEventBookingItemAction::class);

        $confirmedBooking = $createBooking->handle(
            branch: $branch,
            eventSpace: $ballroom,
            title: 'Globex Annual Conference',
            eventType: 'conference',
            startAt: now()->addWeeks(2)->setTime(9, 0),
            endAt: now()->addWeeks(2)->setTime(17, 0),
            corporateAccount: $corporateAccount,
            attendeeCount: 180,
            notes: 'Full-day conference with catered lunch and AV setup.',
            createdBy: $staff['branchManager'],
        );
        $addItem->handle($confirmedBooking, $cateringService, 180);
        $addItem->handle($confirmedBooking, $avService, 2);
        app(ConfirmEventBookingAction::class)->handle($confirmedBooking);

        $createBooking->handle(
            branch: $branch,
            eventSpace: $ballroom,
            title: 'Private Wedding Reception',
            eventType: 'wedding',
            startAt: now()->addMonths(1)->setTime(18, 0),
            endAt: now()->addMonths(1)->setTime(23, 0),
            guest: $guests->random(),
            corporateAccount: $travelAgentAccount,
            attendeeCount: 120,
            createdBy: $staff['branchManager'],
        );
    }

    private function assignToBranch(User $user, Branch $branch, string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);
    }

    private function createSettledStay(Branch $branch, Guest $guest, Room $room, ?CorporateAccount $corporateAccount): void
    {
        $reservation = Reservation::factory()->checkedOut()->create([
            'branch_id' => $branch->id,
            'guest_id' => $guest->id,
            'corporate_account_id' => $corporateAccount?->id,
            'arrival_date' => now()->subDays(10),
            'departure_date' => now()->subDays(7),
        ]);

        ReservationRoom::factory()->create([
            'reservation_id' => $reservation->id,
            'room_type_id' => $room->room_type_id,
            'room_id' => $room->id,
            'rate_cents' => $room->roomType->base_rate_cents,
        ]);

        $nights = 3;
        $roomChargeCents = $room->roomType->base_rate_cents * $nights;
        $taxCents = (int) round($roomChargeCents * 0.12);

        $folio = Folio::factory()->closed()->create([
            'branch_id' => $branch->id,
            'reservation_id' => $reservation->id,
            'guest_id' => $guest->id,
        ]);

        $folio->charges()->create([
            'charge_type' => ChargeType::Room,
            'description' => "{$nights} night(s) — {$room->roomType->name}",
            'amount_cents' => $roomChargeCents,
            'charge_date' => now()->subDays(7)->toDateString(),
        ]);

        $folio->charges()->create([
            'charge_type' => ChargeType::Tax,
            'description' => 'Occupancy tax',
            'amount_cents' => $taxCents,
            'charge_date' => now()->subDays(7)->toDateString(),
        ]);

        Payment::factory()->create([
            'branch_id' => $branch->id,
            'folio_id' => $folio->id,
            'amount_cents' => $roomChargeCents + $taxCents,
        ]);
    }

    private function createInHouseStay(Branch $branch, Guest $guest, Room $room): void
    {
        $reservation = Reservation::factory()->checkedIn()->create([
            'branch_id' => $branch->id,
            'guest_id' => $guest->id,
            'arrival_date' => now()->subDays(1),
            'departure_date' => now()->addDays(2),
        ]);

        ReservationRoom::factory()->create([
            'reservation_id' => $reservation->id,
            'room_type_id' => $room->room_type_id,
            'room_id' => $room->id,
            'rate_cents' => $room->roomType->base_rate_cents,
        ]);

        $room->update(['status' => RoomStatus::Occupied, 'housekeeping_status' => HousekeepingStatus::Dirty]);

        $folio = Folio::factory()->create([
            'branch_id' => $branch->id,
            'reservation_id' => $reservation->id,
            'guest_id' => $guest->id,
        ]);

        $folio->charges()->create([
            'charge_type' => ChargeType::Room,
            'description' => "1 night — {$room->roomType->name}",
            'amount_cents' => $room->roomType->base_rate_cents,
            'charge_date' => now()->subDays(1)->toDateString(),
        ]);

        $folio->update(['balance_cents' => $room->roomType->base_rate_cents]);
    }
}
