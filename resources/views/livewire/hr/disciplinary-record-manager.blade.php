<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Disciplinary Records</h1>
            <p class="text-sm text-slate-500">Visible only to HR and the employee involved.</p>
        </div>

        @can('hr.manage')
            <button wire:click="create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                New record
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-2">
            <div>
                <x-input-label value="Employee" />
                <select wire:model="employeeId" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">Select…</option>
                    @foreach ($this->employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->fullName() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('employeeId')" />
            </div>
            <div>
                <x-input-label value="Incident date" />
                <x-text-input type="date" wire:model="incidentDate" />
                <x-input-error :messages="$errors->get('incidentDate')" />
            </div>
            <div>
                <x-input-label value="Severity" />
                <select wire:model="severity" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($severities as $s)
                        <option value="{{ $s->value }}">{{ ucfirst(str_replace('_', ' ', $s->value)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-full">
                <x-input-label value="Description" />
                <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"></textarea>
                <x-input-error :messages="$errors->get('description')" />
            </div>
            <div class="col-span-full">
                <x-input-label value="Action taken" />
                <textarea wire:model="actionTaken" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"></textarea>
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="space-y-3">
        @forelse ($this->records as $record)
            <div wire:key="record-{{ $record->id }}" class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-slate-800">{{ $record->employee->fullName() }}</p>
                        <p class="text-sm text-slate-500">Reported by {{ $record->reportedBy->name }} on {{ $record->incident_date->format('M j, Y') }}</p>
                    </div>
                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                        {{ ucfirst(str_replace('_', ' ', $record->severity->value)) }}
                    </span>
                </div>

                <p class="mt-2 text-sm text-slate-600">{{ $record->description }}</p>
                @if ($record->action_taken)
                    <p class="mt-1 text-sm text-slate-600"><span class="font-medium">Action taken:</span> {{ $record->action_taken }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-500">No disciplinary records.</p>
        @endforelse
    </div>
</div>
