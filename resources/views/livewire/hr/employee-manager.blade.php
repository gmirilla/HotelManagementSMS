<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Employees</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-text-input type="search" wire:model.live.debounce.300ms="search" placeholder="Search employees…" class="mt-0 w-56" />

            @can('hr.manage')
                <button wire:click="create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    New employee
                </button>
            @endcan
        </div>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-3">
            <div>
                <x-input-label value="First name" />
                <x-text-input type="text" wire:model="firstName" />
                <x-input-error :messages="$errors->get('firstName')" />
            </div>
            <div>
                <x-input-label value="Last name" />
                <x-text-input type="text" wire:model="lastName" />
                <x-input-error :messages="$errors->get('lastName')" />
            </div>
            <div>
                <x-input-label value="Department" />
                <x-text-input type="text" wire:model="department" />
                <x-input-error :messages="$errors->get('department')" />
            </div>
            <div>
                <x-input-label value="Job title" />
                <x-text-input type="text" wire:model="jobTitle" />
                <x-input-error :messages="$errors->get('jobTitle')" />
            </div>
            <div>
                <x-input-label value="Employment type" />
                <select wire:model="employmentType" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($employmentTypes as $type)
                        <option value="{{ $type->value }}">{{ ucfirst(str_replace('_', ' ', $type->value)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Hire date" />
                <x-text-input type="date" wire:model="hireDate" />
                <x-input-error :messages="$errors->get('hireDate')" />
            </div>
            <div>
                <x-input-label value="Base salary (monthly)" />
                <x-text-input type="number" step="0.01" min="0" wire:model="baseSalary" />
                <x-input-error :messages="$errors->get('baseSalary')" />
            </div>
            <div>
                <x-input-label value="Email" />
                <x-text-input type="email" wire:model="email" />
                <x-input-error :messages="$errors->get('email')" />
            </div>
            <div>
                <x-input-label value="Phone" />
                <x-text-input type="text" wire:model="phone" />
                <x-input-error :messages="$errors->get('phone')" />
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Job title</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($employees as $employee)
                    <tr wire:key="employee-{{ $employee->id }}">
                        <td class="px-4 py-2">
                            <p class="font-medium text-slate-800">{{ $employee->fullName() }}</p>
                            <p class="text-xs text-slate-500">{{ $employee->employee_number }}</p>
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ $employee->department }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $employee->job_title }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $employee->status->value === 'active',
                                'bg-amber-100 text-amber-700' => $employee->status->value === 'on_leave',
                                'bg-slate-200 text-slate-600' => $employee->status->value === 'terminated',
                            ])>
                                {{ ucfirst(str_replace('_', ' ', $employee->status->value)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            @can('hr.manage')
                                <button wire:click="edit({{ $employee->id }})" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Edit</button>
                                @if ($employee->status->value !== 'terminated')
                                    <button wire:click="terminate({{ $employee->id }})" wire:confirm="Terminate this employee?" class="ml-3 text-xs font-medium text-red-600 hover:text-red-500">Terminate</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No employees yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $employees->links() }}
    </div>
</div>
