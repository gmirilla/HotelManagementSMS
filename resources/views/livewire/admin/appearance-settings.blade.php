<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Appearance</h1>
        <p class="text-sm text-slate-500">Pick a brand color for your organization. It's applied across the whole app — buttons, links, and highlights.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <form wire:submit="save" class="space-y-6 rounded-xl border border-slate-200/70 bg-white p-6 shadow-sm shadow-slate-900/5 lg:col-span-3">
            <div>
                <x-input-label value="Presets" />
                <p class="mb-3 mt-1 text-xs text-slate-500">A curated set of vibrant, modern colors.</p>
                <div class="grid grid-cols-4 gap-3 sm:grid-cols-8">
                    @foreach ($this->presets as $name => $hex)
                        <button
                            type="button"
                            wire:click="selectPreset('{{ $hex }}')"
                            title="{{ $name }}"
                            class="group flex flex-col items-center gap-1.5"
                        >
                            <span
                                class="h-10 w-10 rounded-full shadow-sm ring-2 ring-offset-2 transition {{ strtolower($selectedColor) === strtolower($hex) ? 'ring-slate-900' : 'ring-transparent group-hover:ring-slate-300' }}"
                                style="background-color: {{ $hex }}"
                            ></span>
                            <span class="text-[11px] text-slate-500">{{ $name }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-slate-100 pt-6">
                <x-input-label value="Custom color" />
                <p class="mb-2 mt-1 text-xs text-slate-500">Or pick any color to match your brand exactly.</p>
                <div class="flex items-center gap-3">
                    <input type="color" wire:model.live="selectedColor" class="h-11 w-14 cursor-pointer rounded-lg border border-slate-300 p-1">
                    <input type="text" wire:model.live.debounce.300ms="selectedColor" maxlength="7" placeholder="#4f46e5" class="block w-32 rounded-lg border-slate-300 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <x-input-error :messages="$errors->get('selectedColor')" />
            </div>

            <div class="border-t border-slate-100 pt-6">
                <x-primary-button class="w-auto">Save appearance</x-primary-button>
            </div>
        </form>

        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-slate-200/70 bg-white p-6 shadow-sm shadow-slate-900/5">
                <h2 class="mb-4 text-sm font-semibold text-slate-700">Live preview</h2>

                <div class="space-y-4">
                    <button type="button" class="inline-flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition" style="background-color: {{ $this->ramp[600] }}">
                        Primary button
                    </button>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                        <span class="text-sm text-slate-600">Navigation link</span>
                        <span class="text-sm font-medium" style="color: {{ $this->ramp[600] }}">Hovered state</span>
                    </div>

                    <div class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium w-fit" style="background-color: {{ $this->ramp[50] }}; color: {{ $this->ramp[700] }}">
                        <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $this->ramp[500] }}"></span>
                        Status pill
                    </div>
                </div>

                <div class="mt-6">
                    <p class="mb-2 text-xs font-medium text-slate-500">Full shade ramp</p>
                    <div class="flex overflow-hidden rounded-lg">
                        @foreach ($this->ramp as $shade => $hex)
                            <div class="h-8 flex-1" style="background-color: {{ $hex }}" title="brand-{{ $shade }}"></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
