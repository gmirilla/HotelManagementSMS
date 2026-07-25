<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Domain\Inventory\Actions\AdjustStockAction;
use App\Domain\Inventory\Actions\IssueStockAction;
use App\Domain\Inventory\Actions\ReceiveStockAction;
use App\Domain\Inventory\Actions\TransferStockAction;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Inventory')]
class ItemManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $name = '';

    public string $sku = '';

    public string $unitOfMeasure = 'unit';

    public string $reorderPoint = '10';

    public ?int $activeWarehouseId = null;

    public bool $showWarehouseForm = false;

    public string $newWarehouseName = '';

    public ?int $movementItemId = null;

    public string $movementMode = 'receive';

    public string $movementQuantity = '';

    public string $movementUnitCost = '';

    public ?int $transferDestinationWarehouseId = null;

    public function updatedActiveWarehouseId(): void
    {
        abort_unless($this->warehouses->contains('id', $this->activeWarehouseId), 403);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100'],
            'unitOfMeasure' => ['required', 'string', 'max:50'],
            'reorderPoint' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return Collection<int, Warehouse>
     */
    #[Computed]
    public function warehouses(): Collection
    {
        $existing = Warehouse::where('branch_id', $this->branchId)->orderBy('name')->get();

        if ($existing->isEmpty()) {
            $existing->push(Warehouse::create(['branch_id' => $this->branchId, 'name' => 'Main Store', 'type' => 'main_store']));
        }

        return $existing;
    }

    #[Computed]
    public function warehouse(): ?Warehouse
    {
        return $this->warehouses->firstWhere('id', $this->activeWarehouseId) ?? $this->warehouses->first();
    }

    #[Computed]
    public function items(): Collection
    {
        return InventoryItem::where('warehouse_id', $this->warehouse->id)
            ->orderBy('name')
            ->get();
    }

    public function createWarehouse(): void
    {
        $this->authorize('create', InventoryItem::class);

        $this->reset(['newWarehouseName']);
        $this->showWarehouseForm = true;
    }

    public function saveWarehouse(): void
    {
        $this->authorize('create', InventoryItem::class);
        $this->validate(['newWarehouseName' => ['required', 'string', 'max:255']]);

        $warehouse = Warehouse::create([
            'branch_id' => $this->branchId,
            'name' => $this->newWarehouseName,
            'type' => 'secondary',
        ]);

        $this->activeWarehouseId = $warehouse->id;
        $this->showWarehouseForm = false;
        unset($this->warehouses);
    }

    public function create(): void
    {
        $this->authorize('create', InventoryItem::class);

        $this->reset(['name', 'sku', 'unitOfMeasure', 'reorderPoint']);
        $this->reorderPoint = '10';
        $this->unitOfMeasure = 'unit';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', InventoryItem::class);

        $this->validate();

        InventoryItem::create([
            'warehouse_id' => $this->warehouse->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'unit_of_measure' => $this->unitOfMeasure,
            'reorder_point' => (int) $this->reorderPoint,
            'quantity_on_hand' => 0,
            'average_cost_cents' => 0,
        ]);

        $this->showForm = false;
        unset($this->items);
    }

    public function startMovement(int $itemId, string $mode): void
    {
        $this->movementItemId = $itemId;
        $this->movementMode = $mode;
        $this->movementQuantity = '';
        $this->movementUnitCost = '';
        $this->transferDestinationWarehouseId = null;
    }

    public function submitMovement(
        ReceiveStockAction $receiveStock,
        IssueStockAction $issueStock,
        AdjustStockAction $adjustStock,
        TransferStockAction $transferStock,
    ): void {
        $item = InventoryItem::findOrFail($this->movementItemId);
        $this->authorize('update', $item);

        $quantity = (int) $this->movementQuantity;

        match ($this->movementMode) {
            'receive' => $receiveStock->handle($item, $quantity, (int) round(((float) $this->movementUnitCost) * 100), auth()->user()),
            'wastage' => $issueStock->handle($item, $quantity, auth()->user(), null, StockMovementType::Wastage),
            'adjust' => $adjustStock->handle($item, $quantity, auth()->user(), 'Manual stocktake correction'),
            'transfer' => $this->submitTransfer($item, $quantity, $transferStock),
            default => null,
        };

        $this->movementItemId = null;
        unset($this->items);
    }

    private function submitTransfer(InventoryItem $item, int $quantity, TransferStockAction $transferStock): void
    {
        $destinationWarehouse = $this->warehouses->firstWhere('id', $this->transferDestinationWarehouseId);

        if (! $destinationWarehouse) {
            throw ValidationException::withMessages(['transferDestinationWarehouseId' => __('Select a destination warehouse.')]);
        }

        $transferStock->handle($item, $destinationWarehouse, $quantity, auth()->user());
    }

    public function render()
    {
        return view('livewire.inventory.item-manager');
    }
}
