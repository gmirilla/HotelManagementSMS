<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Inventory</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }} &middot; {{ $this->warehouse->name }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($this->accessibleBranches->count() > 1)
                <select wire:model.live="branchId" class="rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($this->accessibleBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif

            @can('create', App\Models\InventoryItem::class)
                <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    New item
                </button>
            @endcan
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            <form wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" type="text" wire:model="name" required />
                    <x-input-error :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="sku" value="SKU" />
                    <x-text-input id="sku" type="text" wire:model="sku" required />
                    <x-input-error :messages="$errors->get('sku')" />
                </div>
                <div>
                    <x-input-label for="unitOfMeasure" value="Unit of measure" />
                    <x-text-input id="unitOfMeasure" type="text" wire:model="unitOfMeasure" required />
                </div>
                <div>
                    <x-input-label for="reorderPoint" value="Reorder point" />
                    <x-text-input id="reorderPoint" type="number" min="0" wire:model="reorderPoint" required />
                </div>
                <div class="col-span-full flex gap-3">
                    <x-primary-button class="w-auto">Save</x-primary-button>
                    <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">On hand</th>
                    <th class="px-4 py-3">Avg. cost</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->items as $item)
                    <tr wire:key="item-{{ $item->id }}">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ $item->name }}</p>
                            @if ($item->isBelowReorderPoint())
                                <span class="text-xs font-medium text-amber-600">Below reorder point</span>
                            @endif
                            @if ($item->isNearExpiry())
                                <span class="ml-2 text-xs font-medium text-red-600">Expiring soon</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->sku }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $item->quantity_on_hand }} {{ $item->unit_of_measure }}</td>
                        <td class="px-4 py-3 text-slate-500">${{ number_format($item->average_cost_cents / 100, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $item)
                                <div class="flex justify-end gap-2 text-xs font-medium">
                                    <button wire:click="startMovement({{ $item->id }}, 'receive')" class="text-brand-600 hover:text-brand-500">Receive</button>
                                    <button wire:click="startMovement({{ $item->id }}, 'wastage')" class="text-red-600 hover:text-red-500">Wastage</button>
                                    <button wire:click="startMovement({{ $item->id }}, 'adjust')" class="text-slate-600 hover:text-slate-800">Adjust</button>
                                </div>
                            @endcan

                            @if ($movementItemId === $item->id)
                                <form wire:submit="submitMovement" class="mt-2 flex items-center justify-end gap-2">
                                    <input type="number" wire:model="movementQuantity" placeholder="Qty" class="w-20 rounded-md border-slate-300 text-sm">
                                    @if ($movementMode === 'receive')
                                        <input type="number" step="0.01" wire:model="movementUnitCost" placeholder="Unit cost" class="w-24 rounded-md border-slate-300 text-sm">
                                    @endif
                                    <button type="submit" class="rounded-md bg-brand-600 px-2 py-1 text-xs font-medium text-white hover:bg-brand-500">Go</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No inventory items yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
