<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Attendance</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <x-text-input type="date" wire:model.live="workDate" class="mt-0 w-48" />
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Clock in</th>
                    <th class="px-4 py-3">Clock out</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->rows as $row)
                    <tr wire:key="attendance-{{ $row['employee']->id }}">
                        <td class="px-4 py-2 font-medium text-slate-800">{{ $row['employee']->fullName() }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $row['record']?->clock_in_at?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $row['record']?->clock_out_at?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($row['record'])
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                    {{ ucfirst(str_replace('_', ' ', $row['record']->status->value)) }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400">Not recorded</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-xs">
                            @can('hr.manage')
                                @if (! $row['record']?->clock_in_at)
                                    <button wire:click="clockIn({{ $row['employee']->id }})" class="font-medium text-brand-600 hover:text-brand-500">Clock in</button>
                                @elseif (! $row['record']?->clock_out_at)
                                    <button wire:click="clockOut({{ $row['employee']->id }})" class="font-medium text-brand-600 hover:text-brand-500">Clock out</button>
                                @endif
                                <button wire:click="markStatus({{ $row['employee']->id }}, 'absent')" class="ml-3 font-medium text-red-600 hover:text-red-500">Mark absent</button>
                                <button wire:click="markStatus({{ $row['employee']->id }}, 'on_leave')" class="ml-3 font-medium text-amber-600 hover:text-amber-500">Mark on leave</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No active employees.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
