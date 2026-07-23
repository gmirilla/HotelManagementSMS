<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Leave</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <div class="flex gap-3">
            @if ($this->isHr)
                <button wire:click="createType" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-300 hover:bg-slate-50">
                    New leave type
                </button>
            @endif
            <button wire:click="openRequestForm" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Request leave
            </button>
        </div>
    </div>

    <div class="mb-6 flex gap-1 rounded-lg bg-slate-100 p-1 text-sm font-medium">
        <button wire:click="$set('tab', 'requests')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'requests', 'text-slate-500' => $tab !== 'requests'])>
            Requests
        </button>
        <button wire:click="$set('tab', 'balances')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'balances', 'text-slate-500' => $tab !== 'balances'])>
            Balances
        </button>
        @if ($this->isHr)
            <button wire:click="$set('tab', 'types')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'types', 'text-slate-500' => $tab !== 'types'])>
                Leave Types
            </button>
        @endif
    </div>

    @if ($showForm)
        <form wire:submit="submitRequest" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-2">
            @if ($this->isHr)
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
            @endif
            <div>
                <x-input-label value="Leave type" />
                <select wire:model="leaveTypeId" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">Select…</option>
                    @foreach ($this->leaveTypes as $leaveType)
                        <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('leaveTypeId')" />
            </div>
            <div>
                <x-input-label value="Start date" />
                <x-text-input type="date" wire:model="startDate" />
                <x-input-error :messages="$errors->get('startDate')" />
            </div>
            <div>
                <x-input-label value="End date" />
                <x-text-input type="date" wire:model="endDate" />
                <x-input-error :messages="$errors->get('endDate')" />
            </div>
            <div class="col-span-full">
                <x-input-label value="Reason" />
                <textarea wire:model="reason" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"></textarea>
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Submit request</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    @if ($showTypeForm)
        <form wire:submit="saveType" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-3">
            <div>
                <x-input-label value="Name" />
                <x-text-input type="text" wire:model="typeName" />
                <x-input-error :messages="$errors->get('typeName')" />
            </div>
            <div>
                <x-input-label value="Days per year" />
                <x-text-input type="number" min="0" max="365" wire:model="typeDaysPerYear" />
                <x-input-error :messages="$errors->get('typeDaysPerYear')" />
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="typeIsPaid" class="rounded border-slate-300">
                    Paid leave
                </label>
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showTypeForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    @if ($tab === 'requests')
        <div class="space-y-3">
            @forelse ($this->leaveRequests as $request)
                <div wire:key="leave-{{ $request->id }}" class="rounded-lg border border-slate-200 bg-white p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-slate-800">{{ $request->employee->fullName() }} — {{ $request->leaveType->name }}</p>
                            <p class="text-sm text-slate-500">
                                {{ $request->start_date->format('M j, Y') }} – {{ $request->end_date->format('M j, Y') }}
                                ({{ $request->days_requested }} day(s))
                            </p>
                            @if ($request->reason)
                                <p class="mt-1 text-sm text-slate-600">{{ $request->reason }}</p>
                            @endif
                            @if ($request->status->value === 'rejected' && $request->rejection_reason)
                                <p class="mt-1 text-sm text-red-600">Rejected: {{ $request->rejection_reason }}</p>
                            @endif
                        </div>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-amber-100 text-amber-700' => $request->status->value === 'pending',
                            'bg-emerald-100 text-emerald-700' => $request->status->value === 'approved',
                            'bg-red-100 text-red-700' => $request->status->value === 'rejected',
                            'bg-slate-200 text-slate-600' => $request->status->value === 'cancelled',
                        ])>
                            {{ ucfirst($request->status->value) }}
                        </span>
                    </div>

                    @if ($this->isHr && $request->status->value === 'pending')
                        <div class="mt-3 flex items-center gap-3 border-t border-slate-100 pt-3">
                            <button wire:click="approve({{ $request->id }})" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">Approve</button>
                            <button wire:click="startReject({{ $request->id }})" class="text-sm font-medium text-red-600 hover:text-red-500">Reject</button>
                        </div>

                        @if ($rejectingRequestId === $request->id)
                            <form wire:submit="reject" class="mt-3 flex items-center gap-2">
                                <x-text-input type="text" wire:model="rejectionReason" placeholder="Reason for rejection" class="mt-0 flex-1" />
                                <x-primary-button class="w-auto">Confirm</x-primary-button>
                            </form>
                            <x-input-error :messages="$errors->get('rejectionReason')" />
                        @endif
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No leave requests.</p>
            @endforelse
        </div>
    @endif

    @if ($tab === 'balances')
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        @foreach ($this->leaveTypes as $leaveType)
                            <th class="px-4 py-3">{{ $leaveType->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->employees as $employee)
                        <tr wire:key="balance-{{ $employee->id }}">
                            <td class="px-4 py-2 font-medium text-slate-800">{{ $employee->fullName() }}</td>
                            @foreach ($this->leaveTypes as $leaveType)
                                <td class="px-4 py-2 text-slate-600">{{ $this->remainingDays($employee, $leaveType) }} days left</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="99" class="px-4 py-6 text-center text-slate-500">No employees yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($tab === 'types' && $this->isHr)
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Days/year</th><th class="px-4 py-3">Paid</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->leaveTypes as $leaveType)
                        <tr wire:key="type-{{ $leaveType->id }}">
                            <td class="px-4 py-2 text-slate-800">{{ $leaveType->name }}</td>
                            <td class="px-4 py-2 text-slate-600">{{ $leaveType->days_per_year }}</td>
                            <td class="px-4 py-2 text-slate-600">{{ $leaveType->is_paid ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">No leave types configured yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
