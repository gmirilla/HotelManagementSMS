<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Guest &amp; Folio Summary</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if ($this->accessibleBranches->count() > 1)
                <select wire:model.live="branchId" class="rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($this->accessibleBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif

            <button wire:click="exportCsv" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                Export CSV
            </button>
            <button wire:click="exportPdf" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                Export PDF
            </button>
        </div>
    </div>

    <div class="mb-6 flex items-end gap-3">
        <div>
            <x-input-label value="Arrival from" />
            <x-text-input type="date" wire:model.live="startDate" />
        </div>
        <div>
            <x-input-label value="Arrival to" />
            <x-text-input type="date" wire:model.live="endDate" />
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <div class="rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm shadow-slate-900/5">
            <p class="text-xs uppercase text-slate-400">Total Guests</p>
            <p class="text-xl font-semibold text-slate-900">{{ $this->summary['guest_count'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm shadow-slate-900/5">
            <p class="text-xs uppercase text-slate-400">Reservations</p>
            <p class="text-xl font-semibold text-slate-900">{{ $this->summary['reservation_count'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm shadow-slate-900/5">
            <p class="text-xs uppercase text-slate-400">Charges</p>
            <p class="text-xl font-semibold text-slate-900">{{ number_format($this->summary['charges_total_cents'] / 100, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm shadow-slate-900/5">
            <p class="text-xs uppercase text-slate-400">Payments</p>
            <p class="text-xl font-semibold text-slate-900">{{ number_format($this->summary['payments_total_cents'] / 100, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm shadow-slate-900/5">
            <p class="text-xs uppercase text-slate-400">Outstanding</p>
            <p class="text-xl font-semibold {{ $this->summary['balance_total_cents'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                {{ number_format($this->summary['balance_total_cents'] / 100, 2) }}
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Guest</th>
                    <th class="px-4 py-3">Room(s)</th>
                    <th class="px-4 py-3">Arrival</th>
                    <th class="px-4 py-3">Departure</th>
                    <th class="px-4 py-3">Folio Status</th>
                    <th class="px-4 py-3 text-right">Charges</th>
                    <th class="px-4 py-3 text-right">Payments</th>
                    <th class="px-4 py-3 text-right">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->reservations as $reservation)
                    <tr wire:key="res-{{ $reservation->id }}">
                        <td class="px-4 py-2 text-slate-700">{{ $reservation->guest->fullName() }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $reservation->rooms->pluck('room.room_number')->filter()->implode(', ') }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $reservation->arrival_date->format('M j, Y') }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $reservation->departure_date->format('M j, Y') }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $reservation->folio ? ucfirst($reservation->folio->status->value) : 'No folio' }}</td>
                        <td class="px-4 py-2 text-right text-slate-800">{{ number_format(($reservation->folio->charges_total_cents ?? 0) / 100, 2) }}</td>
                        <td class="px-4 py-2 text-right text-slate-800">{{ number_format(($reservation->folio->payments_total_cents ?? 0) / 100, 2) }}</td>
                        <td class="px-4 py-2 text-right font-medium {{ ($reservation->folio->balance_cents ?? 0) > 0 ? 'text-red-600' : 'text-slate-800' }}">
                            {{ number_format(($reservation->folio->balance_cents ?? 0) / 100, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">No guests in this date range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
