<?php

declare(strict_types=1);

namespace App\Livewire\Restaurant;

use App\Domain\Restaurant\Actions\UpdateKitchenItemStatusAction;
use App\Domain\Restaurant\Enums\KitchenStatus;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\RestaurantOrderItem;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kitchen Display')]
class KitchenDisplay extends Component
{
    use InteractsWithActiveBranch;

    #[Computed]
    public function items(): Collection
    {
        return RestaurantOrderItem::whereHas('order', function ($query) {
            $query->where('branch_id', $this->branchId)->where('status', OrderStatus::SentToKitchen);
        })
            ->whereIn('kitchen_status', [KitchenStatus::Queued, KitchenStatus::Preparing, KitchenStatus::Ready])
            ->with(['menuItem', 'order.table'])
            ->orderBy('created_at')
            ->get();
    }

    public function advance(int $itemId, UpdateKitchenItemStatusAction $updateStatus): void
    {
        $item = RestaurantOrderItem::findOrFail($itemId);
        $this->authorize('updateKitchenStatus', $item->order);

        $next = match ($item->kitchen_status) {
            KitchenStatus::Queued => KitchenStatus::Preparing,
            KitchenStatus::Preparing => KitchenStatus::Ready,
            KitchenStatus::Ready => KitchenStatus::Served,
            KitchenStatus::Served => KitchenStatus::Served,
        };

        $updateStatus->handle($item, $next);
        unset($this->items);
    }

    public function render()
    {
        return view('livewire.restaurant.kitchen-display');
    }
}
