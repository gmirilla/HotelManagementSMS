<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Marketing Campaigns</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        @can('crm.manage')
            <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                New campaign
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6 sm:grid-cols-2">
            <div>
                <x-input-label value="Name" />
                <x-text-input type="text" wire:model="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label value="Channel" />
                <select wire:model="channel" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($channels as $c)
                        <option value="{{ $c->value }}">{{ mb_strtoupper($c->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-full">
                <x-input-label value="Message" />
                <textarea wire:model="message" rows="3" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"></textarea>
                <x-input-error :messages="$errors->get('message')" />
            </div>
            <div>
                <x-input-label value="Scheduled at (optional)" />
                <x-text-input type="datetime-local" wire:model="scheduledAt" />
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Channel</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->campaigns as $campaign)
                    <tr wire:key="campaign-{{ $campaign->id }}">
                        <td class="px-4 py-2 text-slate-800">{{ $campaign->name }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ mb_strtoupper($campaign->channel->value) }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst($campaign->status->value) }}</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            @can('crm.manage')
                                @if ($campaign->status->value !== 'sent')
                                    <button wire:click="markSent({{ $campaign->id }})" class="text-xs font-medium text-brand-600 hover:text-brand-500">Mark sent</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No campaigns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
