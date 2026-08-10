<?php

declare(strict_types=1);

namespace App\Livewire\Restaurant;

use App\Domain\Restaurant\Enums\OutletType;
use App\Domain\Restaurant\Enums\TableStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOutlet;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\ValidationException;
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

        $category = MenuCategory::findOrFail($categoryId);
        abort_unless($category->outlet_id === $this->selectedOutletId, 403);

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

        $category = MenuCategory::findOrFail($this->menuCategoryId);
        abort_unless($category->outlet_id === $this->selectedOutletId, 403);

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

        $item = MenuItem::with('category')->findOrFail($itemId);
        abort_unless($item->category->outlet_id === $this->selectedOutletId, 403);

        $item->update(['is_available' => ! $item->is_available]);

        unset($this->categories);
    }

    public function toggleTableReservation(int $tableId): void
    {
        $this->authorizeRestaurantManage();

        $table = RestaurantTable::findOrFail($tableId);
        abort_unless($table->outlet_id === $this->selectedOutletId, 403);

        $table->update([
            'status' => match ($table->status) {
                TableStatus::Free => TableStatus::Reserved,
                TableStatus::Reserved => TableStatus::Free,
                TableStatus::Occupied => throw ValidationException::withMessages(['table' => __('An occupied table cannot be reserved or unreserved.')]),
            },
        ]);

        unset($this->tables);
    }

    private function authorizeRestaurantManage(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('restaurant.manage'), 403);
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

        return view('livewire.restaurant.menu-manager', ['outletTypes' => OutletType::cases()]);
    }
}
