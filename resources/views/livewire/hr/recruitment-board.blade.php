<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Recruitment</h1>

        @can('hr.manage')
            <button wire:click="createOpening" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                New job opening
            </button>
        @endcan
    </div>

    @if ($showOpeningForm)
        <form wire:submit="saveOpening" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-2">
            <div>
                <x-input-label value="Title" />
                <x-text-input type="text" wire:model="title" />
                <x-input-error :messages="$errors->get('title')" />
            </div>
            <div>
                <x-input-label value="Department" />
                <x-text-input type="text" wire:model="department" />
                <x-input-error :messages="$errors->get('department')" />
            </div>
            <div class="col-span-full">
                <x-input-label value="Description" />
                <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"></textarea>
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showOpeningForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white lg:col-span-1">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Opening</th><th class="px-4 py-3">Candidates</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->jobOpenings as $opening)
                        <tr wire:key="opening-{{ $opening->id }}" wire:click="select({{ $opening->id }})" @class(['cursor-pointer hover:bg-slate-50', 'bg-indigo-50' => $selectedOpeningId === $opening->id])>
                            <td class="px-4 py-2">
                                <p class="font-medium text-slate-800">{{ $opening->title }}</p>
                                <p class="text-xs text-slate-500">{{ $opening->department }} — {{ ucfirst(str_replace('_', ' ', $opening->status->value)) }}</p>
                            </td>
                            <td class="px-4 py-2 text-slate-600">{{ $opening->candidates_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-center text-slate-500">No job openings yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="lg:col-span-2">
            @if ($this->selectedOpening)
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-800">{{ $this->selectedOpening->title }}</p>
                        <p class="text-sm text-slate-500">{{ $this->selectedOpening->department }}</p>
                    </div>
                    @can('hr.manage')
                        <div class="flex gap-3">
                            <button wire:click="addCandidate" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Add candidate</button>
                            @if ($this->selectedOpening->status->value === 'open')
                                <button wire:click="closeOpening({{ $this->selectedOpening->id }})" class="text-sm font-medium text-red-600 hover:text-red-500">Close opening</button>
                            @endif
                        </div>
                    @endcan
                </div>

                @if ($showCandidateForm)
                    <form wire:submit="saveCandidate" class="mb-4 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-3">
                        <div>
                            <x-input-label value="Name" />
                            <x-text-input type="text" wire:model="candidateName" />
                            <x-input-error :messages="$errors->get('candidateName')" />
                        </div>
                        <div>
                            <x-input-label value="Email" />
                            <x-text-input type="email" wire:model="candidateEmail" />
                            <x-input-error :messages="$errors->get('candidateEmail')" />
                        </div>
                        <div>
                            <x-input-label value="Phone" />
                            <x-text-input type="text" wire:model="candidatePhone" />
                        </div>
                        <div class="col-span-full flex gap-3">
                            <x-primary-button class="w-auto">Save</x-primary-button>
                            <button type="button" wire:click="$set('showCandidateForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                        </div>
                    </form>
                @endif

                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-3">Candidate</th><th class="px-4 py-3">Stage</th><th class="px-4 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($this->selectedOpening->candidates as $candidate)
                                <tr wire:key="candidate-{{ $candidate->id }}">
                                    <td class="px-4 py-2">
                                        <p class="font-medium text-slate-800">{{ $candidate->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $candidate->email }}</p>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst($candidate->stage->value) }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        @can('hr.manage')
                                            <select wire:change="advanceStage({{ $candidate->id }}, $event.target.value)" class="rounded-md border-slate-300 text-xs shadow-sm">
                                                @foreach ($stages as $stage)
                                                    <option value="{{ $stage->value }}" @selected($candidate->stage === $stage)>{{ ucfirst($stage->value) }}</option>
                                                @endforeach
                                            </select>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">No candidates yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-slate-500">Select a job opening to view its candidate pipeline.</p>
            @endif
        </div>
    </div>
</div>
