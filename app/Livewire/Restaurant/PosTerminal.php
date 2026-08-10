<?php

declare(strict_types=1);

namespace App\Livewire\Restaurant;

use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Restaurant\Actions\AddOrderItemAction;
use App\Domain\Restaurant\Actions\CloseRestaurantOrderAction;
use App\Domain\Restaurant\Actions\CreateRestaurantOrderAction;
use App\Domain\Restaurant\Actions\SendOrderToKitchenAction;
use App\Domain\Restaurant\Actions\VoidRestaurantOrderAction;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Restaurant\Enums\OrderType;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOutlet;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('POS')]
class PosTerminal extends Component
{
    use InteractsWithActiveBranch;

    public ?int $selectedOutletId = null;

    public ?int $activeOrderId = null;

    public string $guestSearch = '';

    public bool $hasSearchedGuests = false;

    public bool $showVoidForm = false;

    public string $voidReason = '';

    public function updatedSelectedOutletId(): void
    {
        abort_unless($this->outlets->contains('id', $this->selectedOutletId), 403);
    }

    #[Computed]
    public function outlets(): Collection
    {
        return RestaurantOutlet::where('branch_id', $this->branchId)->orderBy('name')->get();
    }

    #[Computed]
    public function tables(): SupportCollection
    {
        return $this->selectedOutletId
            ? RestaurantTable::where('outlet_id', $this->selectedOutletId)->orderBy('label')->get()
            : collect();
    }

    /**
     * @return SupportCollection<string, SupportCollection<int, MenuItem>>
     */
    #[Computed]
    public function menu(): SupportCollection
    {
        return $this->selectedOutletId
            ? MenuItem::whereHas('category', fn ($q) => $q->where('outlet_id', $this->selectedOutletId))
                ->where('is_available', true)
                ->with('category')
                ->get()
                ->groupBy(fn (MenuItem $item) => $item->category->name)
            : collect();
    }

    #[Computed]
    public function activeOrder(): ?RestaurantOrder
    {
        return $this->activeOrderId
            ? RestaurantOrder::with(['items.menuItem', 'table', 'guest'])->find($this->activeOrderId)
            : null;
    }

    /**
     * Matches by guest name or by the room number of their current
     * checked-in stay, since front-of-house staff more often know "room
     * 204 wants room service" than the guest's name.
     */
    #[Computed]
    public function guestResults(): SupportCollection
    {
        if (mb_strlen($this->guestSearch) < 2) {
            return collect();
        }

        return Guest::where('tenant_id', auth()->user()->tenant_id)
            ->where(function ($query) {
                $query->where('first_name', 'like', "%{$this->guestSearch}%")
                    ->orWhere('last_name', 'like', "%{$this->guestSearch}%")
                    ->orWhereHas('reservations', function ($reservationQuery) {
                        $reservationQuery->where('status', ReservationStatus::CheckedIn)
                            ->whereHas('rooms.room', function ($roomQuery) {
                                $roomQuery->where('room_number', 'like', "%{$this->guestSearch}%");
                            });
                    });
            })
            ->with(['reservations' => fn ($query) => $query->where('status', ReservationStatus::CheckedIn)->with('rooms.room')])
            ->limit(10)
            ->get();
    }

    /**
     * guestSearch is bound with a deferred (non-live) wire:model, so typing
     * alone never triggers a request — this is the wire:submit target that
     * actually runs a search, on either a button click or pressing Enter in
     * the search field. The search itself happens in guestResults() once
     * this round trip syncs guestSearch; this method only flips the flag the
     * view uses to distinguish "haven't searched yet" from "searched, no
     * matches" for the empty-state message.
     */
    public function searchGuests(): void
    {
        $this->hasSearchedGuests = true;
    }

    public function selectTable(int $tableId, CreateRestaurantOrderAction $createOrder): void
    {
        $table = RestaurantTable::findOrFail($tableId);
        abort_unless($table->outlet_id === $this->selectedOutletId, 403);

        if ($table->status === TableStatus::Occupied) {
            $order = RestaurantOrder::where('table_id', $table->id)
                ->whereIn('status', [OrderStatus::Open, OrderStatus::SentToKitchen, OrderStatus::Served])
                ->latest()
                ->firstOrFail();

            $this->authorize('update', $order);
            $this->activeOrderId = $order->id;

            return;
        }

        $this->authorize('create', RestaurantOrder::class);

        $order = $createOrder->handle($this->branchId, $this->selectedOutletId, auth()->user(), OrderType::DineIn, $table);

        $this->activeOrderId = $order->id;
        unset($this->tables);
    }

    public function startRoomServiceOrder(int $guestId, CreateRestaurantOrderAction $createOrder): void
    {
        $this->authorize('create', RestaurantOrder::class);

        $guest = Guest::findOrFail($guestId);
        abort_unless($guest->tenant_id === auth()->user()->tenant_id, 403);

        $order = $createOrder->handle($this->branchId, $this->selectedOutletId, auth()->user(), OrderType::RoomService, null, $guestId);

        $this->activeOrderId = $order->id;
        $this->guestSearch = '';
        $this->hasSearchedGuests = false;
    }

    public function addItem(int $menuItemId, AddOrderItemAction $addOrderItem): void
    {
        $order = RestaurantOrder::findOrFail($this->activeOrderId);
        $this->authorize('update', $order);

        $addOrderItem->handle($order, MenuItem::findOrFail($menuItemId), 1);
        unset($this->activeOrder);
    }

    public function sendToKitchen(SendOrderToKitchenAction $sendToKitchen): void
    {
        $order = RestaurantOrder::findOrFail($this->activeOrderId);
        $this->authorize('update', $order);

        $sendToKitchen->handle($order);
        unset($this->activeOrder);
    }

    public function closeOrder(CloseRestaurantOrderAction $closeOrder): void
    {
        $order = RestaurantOrder::findOrFail($this->activeOrderId);
        $this->authorize('close', $order);

        $closeOrder->handle($order, auth()->user());

        $this->activeOrderId = null;
        unset($this->tables, $this->activeOrder);
    }

    public function voidOrder(VoidRestaurantOrderAction $voidOrder): void
    {
        $order = RestaurantOrder::findOrFail($this->activeOrderId);
        $this->authorize('void', $order);

        $voidOrder->handle($order, $this->voidReason);

        $this->activeOrderId = null;
        $this->showVoidForm = false;
        $this->voidReason = '';
        unset($this->tables, $this->activeOrder);
    }

    public function render()
    {
        // Not in mount(): Livewire calls a component's own mount() before
        // its traits' mount hooks, so $this->branchId (set by
        // InteractsWithActiveBranch::mountInteractsWithActiveBranch) isn't
        // populated yet there — defaulting the selection here instead,
        // after the full mount cycle has run, is what actually lets it see
        // the branch's outlets. ??= so a real user selection is never
        // clobbered on a later render.
        $this->selectedOutletId ??= $this->outlets->first()?->id;

        return view('livewire.restaurant.pos-terminal');
    }
}
