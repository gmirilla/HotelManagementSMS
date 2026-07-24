<?php

declare(strict_types=1);

/**
 * Full-page Livewire render tests for the Events module, in both empty and
 * populated states — see CrmLivewireRenderTest.php /
 * RestaurantLivewireRenderTest.php for why this practice is mandatory here.
 */

use App\Domain\Event\Actions\AddEventBookingItemAction;
use App\Domain\Event\Actions\CreateEventBookingAction;
use App\Livewire\Events\EventBookingManager;
use App\Livewire\Events\EventSpaceManager;
use App\Models\Branch;
use App\Models\EventService;
use App\Models\EventSpace;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'events.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('events.manage');

    $this->branch = Branch::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('event space manager renders both tabs with no data yet', function (): void {
    $component = Livewire::actingAs($this->staff)->test(EventSpaceManager::class)->assertOk();

    $component->set('tab', 'services')->assertOk();
});

test('event space manager renders both tabs with spaces and services present', function (): void {
    EventSpace::factory()->create(['branch_id' => $this->branch->id]);
    EventService::factory()->create(['branch_id' => $this->branch->id]);

    $component = Livewire::actingAs($this->staff)->test(EventSpaceManager::class)->assertOk();

    $component->set('tab', 'services')->assertOk();
});

test('event booking manager renders with no bookings yet', function (): void {
    Livewire::actingAs($this->staff)->test(EventBookingManager::class)->assertOk();
});

test('event booking manager renders a selected booking\'s consolidated bill', function (): void {
    $space = EventSpace::factory()->create(['branch_id' => $this->branch->id, 'hourly_rate_cents' => 20000]);
    $service = EventService::factory()->create(['branch_id' => $this->branch->id, 'unit_price_cents' => 5000]);

    $booking = app(CreateEventBookingAction::class)->handle(
        $this->branch, $space, 'Test Conference', 'conference',
        Carbon::parse('2026-09-01 09:00'), Carbon::parse('2026-09-01 13:00'),
    );
    app(AddEventBookingItemAction::class)->handle($booking, $service, 4);

    $component = Livewire::actingAs($this->staff)->test(EventBookingManager::class)->assertOk();

    // bill is render()-only view data (not a component property), so it
    // must be asserted via rendered output. 4h * 20000c venue + 4 * 5000c
    // items = 100000c = $1,000.00 total.
    $component->call('select', $booking->id)->assertOk()->assertSee('$1,000.00');
});
