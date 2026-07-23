<?php

declare(strict_types=1);

namespace App\Livewire\Restaurant;

use App\Domain\Restaurant\Enums\OutletType;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOutlet;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Restaurant Menu')]
class MenuManager extends Component
{
    use InteractsWithActiveBranch;

    public ?int $selectedOutletId = null;

    public bool $showOutletForm = false;

    public string $outletName = '';

    public string $outletType = 'restaurant';

    public bool $showTableForm = false;

    public string $tableLabel = '';

    public string $tableSeats = '2';

    public bool $showItemForm = false;

    public ?int $editingItemId = null;

    public ?int $menuCategoryId = null;

    public string $itemName = '';

    public string $itemPrice = '';

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

    #[Computed]
    public function categories(): SupportCollection
    {
        return $this->selectedOutletId
            ? MenuCategory::where('outlet_id', $this->selectedOutletId)->with('items')->orderBy('display_order')->get()
            : collect();
    }

    public function saveOutlet(): void
    {
        $this->authorizeRestaurantManage();
        $this->validate(['outletName' => ['required', 'string', 'max:255']]);

        $outlet = RestaurantOutlet::create([
            'branch_id' => $this->branchId,
            'name' => $this->outletName,
            'outlet_type' => $this->outletType,
        ]);

        $this->selectedOutletId = $outlet->id;
        $this->reset(['outletName', 'showOutletForm']);
        unset($this->outlets);
    }

    public function saveTable(): void
    {
        $this->authorizeRestaurantManage();
        $this->validate(['tableLabel' => ['required', 'string', 'max:50'], 'tableSeats' => ['required', 'integer', 'min:1']]);

        RestaurantTable::create([
            'outlet_id' => $this->selectedOutletId,
            'label' => $this->tableLabel,
            'seats' => (int) $this->tableSeats,
        ]);

        $this->reset(['tableLabel', 'showTableForm']);
        $this->tableSeats = '2';
        unset($this->tables);
    }

    public function createItem(int $categoryId): void
    {
        $this->authorizeRestaurantManage();

        $this->menuCategoryId = $categoryId;
        $this->reset(['itemName', 'itemPrice', 'editingItemId']);
        $this->showItemForm = true;
    }

    public function saveItem(): void
    {
        $this->authorizeRestaurantManage();
        $this->validate([
            'itemName' => ['required', 'string', 'max:255'],
            'itemPrice' => ['required', 'numeric', 'min:0'],
        ]);

        MenuItem::create([
            'menu_category_id' => $this->menuCategoryId,
            'name' => $this->itemName,
            'price_cents' => (int) round(((float) $this->itemPrice) * 100),
        ]);

        $this->showItemForm = false;
        unset($this->categories);
    }

    public function addCategory(): void
    {
        $this->authorizeRestaurantManage();

        MenuCategory::create([
            'outlet_id' => $this->selectedOutletId,
            'name' => 'New Category',
            'display_order' => $this->categories->count(),
        ]);

        unset($this->categories);
    }

    public function toggleAvailability(int $itemId): void
    {
        $this->authorizeRestaurantManage();

        $item = MenuItem::findOrFail($itemId);
        $item->update(['is_available' => ! $item->is_available]);

        unset($this->categories);
    }

    private function authorizeRestaurantManage(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('restaurant.manage'), 403);
    }

    public function render()
    {
        return view('livewire.restaurant.menu-manager', ['outletTypes' => OutletType::cases()]);
    }
}
