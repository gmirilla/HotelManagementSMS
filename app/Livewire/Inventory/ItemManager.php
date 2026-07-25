<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Domain\Inventory\Actions\AdjustStockAction;
use App\Domain\Inventory\Actions\IssueStockAction;
use App\Domain\Inventory\Actions\ReceiveStockAction;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
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

    public ?int $movementItemId = null;

    public string $movementMode = 'receive';

    public string $movementQuantity = '';

    public string $movementUnitCost = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100'],
            'unitOfMeasure' => ['required', 'string', 'max:50'],
            'reorderPoint' => ['required', 'integer', 'min:0'],
        ];
    }

    #[Computed]
    public function warehouse(): ?Warehouse
    {
        return Warehouse::firstOrCreate(
            ['branch_id' => $this->branchId, 'type' => 'main_store'],
            ['name' => 'Main Store'],
        );
    }

    #[Computed]
    public function items(): Collection
    {
        return InventoryItem::where('warehouse_id', $this->warehouse->id)
            ->orderBy('name')
            ->get();
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
    }

    public function submitMovement(
        ReceiveStockAction $receiveStock,
        IssueStockAction $issueStock,
        AdjustStockAction $adjustStock,
    ): void {
        $item = InventoryItem::findOrFail($this->movementItemId);
        $this->authorize('update', $item);

        $quantity = (int) $this->movementQuantity;

        match ($this->movementMode) {
            'receive' => $receiveStock->handle($item, $quantity, (int) round(((float) $this->movementUnitCost) * 100), auth()->user()),
            'wastage' => $issueStock->handle($item, $quantity, auth()->user(), null, StockMovementType::Wastage),
            'adjust' => $adjustStock->handle($item, $quantity, auth()->user(), 'Manual stocktake correction'),
            default => null,
        };

        $this->movementItemId = null;
        unset($this->items);
    }

    public function render()
    {
        return view('livewire.inventory.item-manager');
    }
}
