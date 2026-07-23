<?php

declare(strict_types=1);

namespace App\Livewire\Restaurant;

use App\Domain\Restaurant\Actions\AddOrderItemAction;
use App\Domain\Restaurant\Actions\CloseRestaurantOrderAction;
use App\Domain\Restaurant\Actions\CreateRestaurantOrderAction;
use App\Domain\Restaurant\Actions\SendOrderToKitchenAction;
use App\Domain\Restaurant\Enums\OrderType;
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

    public function mount(): void
    {
        $this->selectedOutletId = $this->outlets->first()?->id;
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

    #[Computed]
    public function guestResults(): SupportCollection
    {
        if (mb_strlen($this->guestSearch) < 2) {
            return collect();
        }

        return Guest::where('tenant_id', auth()->user()->tenant_id)
            ->where('last_name', 'like', "%{$this->guestSearch}%")
            ->limit(10)
            ->get();
    }

    public function startTableOrder(int $tableId, CreateRestaurantOrderAction $createOrder): void
    {
        $this->authorize('create', RestaurantOrder::class);

        $table = RestaurantTable::findOrFail($tableId);
        $order = $createOrder->handle($this->branchId, $this->selectedOutletId, auth()->user(), OrderType::DineIn, $table);

        $this->activeOrderId = $order->id;
        unset($this->tables);
    }

    public function startRoomServiceOrder(int $guestId, CreateRestaurantOrderAction $createOrder): void
    {
        $this->authorize('create', RestaurantOrder::class);

        $order = $createOrder->handle($this->branchId, $this->selectedOutletId, auth()->user(), OrderType::RoomService, null, $guestId);

        $this->activeOrderId = $order->id;
        $this->guestSearch = '';
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

    public function render()
    {
        return view('livewire.restaurant.pos-terminal');
    }
}
