<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Restaurant &amp; Menu</h1>

        @can('restaurant.manage')
            <button wire:click="$set('showOutletForm', true)" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                New outlet
            </button>
        @endcan
    </div>

    @if ($showOutletForm)
        <form wire:submit="saveOutlet" class="mb-6 flex items-end gap-3 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4">
            <div>
                <x-input-label value="Outlet name" />
                <x-text-input type="text" wire:model="outletName" />
                <x-input-error :messages="$errors->get('outletName')" />
            </div>
            <div>
                <x-input-label value="Type" />
                <select wire:model="outletType" class="mt-1 rounded-md border-slate-300 text-sm">
                    @foreach ($outletTypes as $type)
                        <option value="{{ $type->value }}">{{ ucfirst(str_replace('_', ' ', $type->value)) }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button class="w-auto">Save</x-primary-button>
        </form>
    @endif

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach ($this->outlets as $outlet)
            <button wire:click="$set('selectedOutletId', {{ $outlet->id }})"
                @class(['rounded-md px-3 py-1.5 text-sm font-medium', 'bg-brand-600 text-white' => $selectedOutletId === $outlet->id, 'bg-slate-100 text-slate-600' => $selectedOutletId !== $outlet->id])>
                {{ $outlet->name }}
            </button>
        @endforeach
    </div>

    @if ($selectedOutletId)
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-medium text-slate-800">Tables</h2>
                    @can('restaurant.manage')
                        <button wire:click="$set('showTableForm', true)" class="text-sm font-medium text-brand-600 hover:text-brand-500">+ Add</button>
                    @endcan
                </div>

                @if ($showTableForm)
                    <form wire:submit="saveTable" class="mb-3 space-y-2">
                        <x-text-input type="text" wire:model="tableLabel" placeholder="Label" />
                        <x-input-error :messages="$errors->get('tableLabel')" />
                        <x-text-input type="number" min="1" wire:model="tableSeats" placeholder="Seats" />
                        <x-primary-button class="w-auto">Save</x-primary-button>
                    </form>
                @endif

                <div class="flex flex-wrap gap-2">
                    @foreach ($this->tables as $table)
                        <span @class([
                            'rounded-md px-3 py-1.5 text-sm',
                            'bg-emerald-50 text-emerald-700' => $table->status->value === 'free',
                            'bg-brand-50 text-brand-700' => $table->status->value === 'occupied',
                            'bg-amber-50 text-amber-700' => $table->status->value === 'reserved',
                        ])>{{ $table->label }} ({{ $table->seats }})</span>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-2 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-medium text-slate-800">Menu</h2>
                    @can('restaurant.manage')
                        <button wire:click="addCategory" class="text-sm font-medium text-brand-600 hover:text-brand-500">+ Add category</button>
                    @endcan
                </div>

                @foreach ($this->categories as $category)
                    <div class="mb-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-600">{{ $category->name }}</h3>
                            @can('restaurant.manage')
                                <button wire:click="createItem({{ $category->id }})" class="text-xs font-medium text-brand-600 hover:text-brand-500">+ Item</button>
                            @endcan
                        </div>

                        @if ($showItemForm && $menuCategoryId === $category->id)
                            <form wire:submit="saveItem" class="mb-2 flex items-center gap-2">
                                <x-text-input type="text" wire:model="itemName" placeholder="Item name" class="mt-0" />
                                <x-text-input type="number" step="0.01" wire:model="itemPrice" placeholder="Price" class="mt-0 w-24" />
                                <x-primary-button class="w-auto">Save</x-primary-button>
                            </form>
                        @endif

                        <div class="divide-y divide-slate-100 text-sm">
                            @foreach ($category->items as $item)
                                <div class="flex items-center justify-between py-1.5">
                                    <span class="{{ $item->is_available ? 'text-slate-700' : 'text-slate-400 line-through' }}">{{ $item->name }}</span>
                                    <div class="flex items-center gap-3">
                                        <span class="text-slate-500">₦{{ number_format($item->price_cents / 100, 2) }}</span>
                                        @can('restaurant.manage')
                                            <button wire:click="toggleAvailability({{ $item->id }})" class="text-xs text-brand-600 hover:text-brand-500">
                                                {{ $item->is_available ? 'Disable' : 'Enable' }}
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
