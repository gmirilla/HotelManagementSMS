<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Hotel Settings</h1>
        <p class="text-sm text-slate-500">Your organization's name, logo, and defaults new branches start from.</p>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-6 rounded-xl border border-slate-200/70 bg-white p-6 shadow-sm shadow-slate-900/5">
        <div>
            <x-input-label value="Hotel name" />
            <x-text-input type="text" wire:model="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label value="Logo" />
            <div class="mt-2 flex items-center gap-4">
                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="" class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                @elseif ($tenant->logo_path)
                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="" class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-xl font-bold text-white">
                        {{ strtoupper(substr($tenant->name, 0, 1)) }}
                    </div>
                @endif

                <div>
                    <input type="file" wire:model="logo" accept="image/*" class="block text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                    <p class="mt-1 text-xs text-slate-500">PNG or JPG, up to 2MB.</p>
                    <div wire:loading wire:target="logo" class="mt-1 text-xs text-slate-500">Uploading…</div>
                </div>
            </div>
            <x-input-error :messages="$errors->get('logo')" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label value="Default currency" />
                <x-text-input type="text" wire:model="defaultCurrency" maxlength="3" class="uppercase" placeholder="USD" />
                <p class="mt-1 text-xs text-slate-500">3-letter code. New branches start with this currency.</p>
                <x-input-error :messages="$errors->get('defaultCurrency')" />
            </div>

            <div>
                <x-input-label value="Default timezone" />
                <select wire:model="defaultTimezone" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @foreach ($timezones as $timezone)
                        <option value="{{ $timezone }}">{{ $timezone }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('defaultTimezone')" />
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <x-primary-button class="w-auto">Save settings</x-primary-button>
        </div>
    </form>
</div>
