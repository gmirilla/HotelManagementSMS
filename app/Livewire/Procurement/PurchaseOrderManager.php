<?php

declare(strict_types=1);

namespace App\Livewire\Procurement;

use App\Domain\Procurement\Actions\CreatePurchaseOrderAction;
use App\Domain\Procurement\Actions\ReceiveGoodsAction;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Purchase Orders')]
class PurchaseOrderManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public ?int $supplierId = null;

    /** @var array<int, array{inventory_item_id: string, quantity: string, unit_cost: string}> */
    public array $lines = [['inventory_item_id' => '', 'quantity' => '', 'unit_cost' => '']];

    public ?int $receivingId = null;

    /** @var array<int, string> */
    public array $receiveQuantities = [];

    #[Computed]
    public function purchaseOrders(): Collection
    {
        return PurchaseOrder::where('branch_id', $this->branchId)
            ->with(['supplier', 'items.inventoryItem'])
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function suppliers(): Collection
    {
        return Supplier::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get();
    }

    #[Computed]
    public function inventoryItems(): Collection
    {
        $warehouseIds = Warehouse::where('branch_id', $this->branchId)->pluck('id');

        return InventoryItem::whereIn('warehouse_id', $warehouseIds)->orderBy('name')->get();
    }

    public function addLine(): void
    {
        $this->lines[] = ['inventory_item_id' => '', 'quantity' => '', 'unit_cost' => ''];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function create(): void
    {
        $this->authorize('create', PurchaseOrder::class);
        $this->reset(['supplierId']);
        $this->lines = [['inventory_item_id' => '', 'quantity' => '', 'unit_cost' => '']];
        $this->showForm = true;
    }

    public function save(CreatePurchaseOrderAction $createPurchaseOrder): void
    {
        $this->authorize('create', PurchaseOrder::class);

        $this->validate([
            'supplierId' => ['required', 'integer', 'exists:suppliers,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $lines = collect($this->lines)->map(fn (array $line) => [
            'inventory_item_id' => (int) $line['inventory_item_id'],
            'quantity' => (int) $line['quantity'],
            'unit_cost_cents' => (int) round(((float) $line['unit_cost']) * 100),
        ])->all();

        $createPurchaseOrder->handle($this->branchId, $this->supplierId, auth()->user(), $lines);

        $this->showForm = false;
        unset($this->purchaseOrders);
    }

    public function startReceiving(int $purchaseOrderId): void
    {
        $this->receivingId = $purchaseOrderId;
        $this->receiveQuantities = [];
    }

    public function receive(ReceiveGoodsAction $receiveGoods): void
    {
        $purchaseOrder = PurchaseOrder::findOrFail($this->receivingId);
        $this->authorize('receive', $purchaseOrder);

        $quantities = collect($this->receiveQuantities)
            ->map(fn ($quantity) => (int) $quantity)
            ->filter(fn (int $quantity) => $quantity > 0)
            ->all();

        $receiveGoods->handle($purchaseOrder, $quantities, auth()->user());

        $this->receivingId = null;
        unset($this->purchaseOrders);
    }

    public function render()
    {
        return view('livewire.procurement.purchase-order-manager');
    }
}
