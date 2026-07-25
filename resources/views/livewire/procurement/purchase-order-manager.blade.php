<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Purchase Orders</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        @can('create', App\Models\PurchaseOrder::class)
            <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                New purchase order
            </button>
        @endcan
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <x-input-label for="supplierId" value="Supplier" />
                    <select id="supplierId" wire:model="supplierId" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="">Select a supplier&hellip;</option>
                        @foreach ($this->suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('supplierId')" />
                </div>

                <div class="space-y-2">
                    <x-input-label value="Line items" />
                    @foreach ($lines as $index => $line)
                        <div class="flex items-center gap-2">
                            <select wire:model="lines.{{ $index }}.inventory_item_id" class="flex-1 rounded-md border-slate-300 text-sm">
                                <option value="">Item&hellip;</option>
                                @foreach ($this->inventoryItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" min="1" wire:model="lines.{{ $index }}.quantity" placeholder="Qty" class="w-24 rounded-md border-slate-300 text-sm">
                            <input type="number" step="0.01" min="0" wire:model="lines.{{ $index }}.unit_cost" placeholder="Unit cost" class="w-28 rounded-md border-slate-300 text-sm">
                            <button type="button" wire:click="removeLine({{ $index }})" class="text-sm text-red-600">&times;</button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addLine" class="text-sm font-medium text-brand-600 hover:text-brand-500">+ Add line</button>
                </div>

                <div class="flex gap-3">
                    <x-primary-button class="w-auto">Create purchase order</x-primary-button>
                    <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($this->purchaseOrders as $po)
            <div wire:key="po-{{ $po->id }}" class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-slate-800">{{ $po->po_number }} &middot; {{ $po->supplier->name }}</p>
                        <p class="text-sm text-slate-500">₦{{ number_format($po->total_cents / 100, 2) }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst(str_replace('_', ' ', $po->status->value)) }}</span>
                </div>

                <div class="mt-2 space-y-1 text-sm text-slate-600">
                    @foreach ($po->items as $line)
                        <p>{{ $line->inventoryItem->name }} — {{ $line->quantity_received }}/{{ $line->quantity_ordered }} received</p>
                    @endforeach
                </div>

                @can('receive', $po)
                    @if (in_array($po->status->value, ['sent', 'partially_received']))
                        @if ($receivingId === $po->id)
                            <form wire:submit="receive" class="mt-3 space-y-2 border-t border-slate-100 pt-3">
                                @foreach ($po->items as $line)
                                    @if (! $line->isFullyReceived())
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="w-40">{{ $line->inventoryItem->name }}</span>
                                            <input type="number" min="0" max="{{ $line->outstandingQuantity() }}"
                                                wire:model="receiveQuantities.{{ $line->id }}" placeholder="Qty received"
                                                class="w-28 rounded-md border-slate-300 text-sm">
                                            <span class="text-xs text-slate-400">of {{ $line->outstandingQuantity() }} outstanding</span>
                                        </div>
                                    @endif
                                @endforeach
                                <button type="submit" class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-500">
                                    Record receipt
                                </button>
                            </form>
                        @else
                            <button wire:click="startReceiving({{ $po->id }})" class="mt-3 text-sm font-medium text-brand-600 hover:text-brand-500">
                                Receive goods
                            </button>
                        @endif
                    @endif
                @endcan
            </div>
        @empty
            <p class="text-sm text-slate-500">No purchase orders yet.</p>
        @endforelse
    </div>
</div>
