<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slate-900">Kitchen Display</h1>
        @if ($this->activeBranch)
            <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($this->items as $item)
            @php
                $styles = [
                    'queued' => 'border-slate-200 bg-white',
                    'preparing' => 'border-amber-300 bg-amber-50',
                    'ready' => 'border-emerald-300 bg-emerald-50',
                ];
            @endphp
            <div wire:key="kot-{{ $item->id }}" class="rounded-lg border p-4 {{ $styles[$item->kitchen_status->value] ?? '' }}">
                <p class="text-xs font-medium uppercase text-slate-400">
                    {{ $item->order->table ? 'Table '.$item->order->table->label : 'Room Service' }}
                </p>
                <p class="mt-1 font-medium text-slate-800">{{ $item->quantity }}&times; {{ $item->menuItem->name }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ ucfirst($item->kitchen_status->value) }}</p>

                @can('updateKitchenStatus', $item->order)
                    @if ($item->kitchen_status->value !== 'served')
                        <button wire:click="advance({{ $item->id }})" class="mt-3 rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">
                            Mark {{ $item->kitchen_status->value === 'queued' ? 'preparing' : ($item->kitchen_status->value === 'preparing' ? 'ready' : 'served') }}
                        </button>
                    @endif
                @endcan
            </div>
        @empty
            <p class="col-span-full text-sm text-slate-500">No active kitchen tickets.</p>
        @endforelse
    </div>
</div>
