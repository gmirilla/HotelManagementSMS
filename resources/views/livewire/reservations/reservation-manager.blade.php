<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Reservations</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($this->accessibleBranches->count() > 1)
                <select wire:model.live="branchId" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($this->accessibleBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif

            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search code or guest&hellip;"
                class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

            <select wire:model.live="statusFilter" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ ucfirst(str_replace('_', ' ', $status->value)) }}</option>
                @endforeach
            </select>

            @can('create', App\Models\Reservation::class)
                <a href="{{ route('reservations.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    New reservation
                </a>
            @endcan
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Guest</th>
                    <th class="px-4 py-3">Room type</th>
                    <th class="px-4 py-3">Dates</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reservations as $reservation)
                    <tr wire:key="reservation-{{ $reservation->id }}" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('reservations.show', $reservation) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                {{ $reservation->confirmation_code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $reservation->guest->fullName() }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $reservation->rooms->first()?->roomType?->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $reservation->arrival_date->format('M j') }} &ndash; {{ $reservation->departure_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst(str_replace('_', ' ', $reservation->status->value)) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No reservations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reservations->links() }}</div>
</div>
