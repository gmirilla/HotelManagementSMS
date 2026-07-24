<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">{{ $reservation->confirmation_code }}</h1>
            <p class="text-sm text-slate-500">{{ $reservation->guest->fullName() }} &middot; {{ $reservation->branch->name }}</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-600">{{ ucfirst(str_replace('_', ' ', $reservation->status->value)) }}</span>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5 sm:grid-cols-4">
        <div>
            <p class="text-xs uppercase text-slate-400">Arrival</p>
            <p class="font-medium text-slate-800">{{ $reservation->arrival_date->format('M j, Y') }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-400">Departure</p>
            <p class="font-medium text-slate-800">{{ $reservation->departure_date->format('M j, Y') }}</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-400">Occupants</p>
            <p class="font-medium text-slate-800">{{ $reservation->adults }} adults, {{ $reservation->children }} children</p>
        </div>
        <div>
            <p class="text-xs uppercase text-slate-400">Source</p>
            <p class="font-medium text-slate-800">{{ ucfirst(str_replace('_', ' ', $reservation->source->value)) }}</p>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
        <h2 class="mb-3 font-medium text-slate-800">Room</h2>
        @foreach ($reservation->rooms as $reservationRoom)
            <div class="flex items-center justify-between text-sm">
                <span>{{ $reservationRoom->roomType->name }} @if($reservationRoom->room) &mdash; Room {{ $reservationRoom->room->room_number }} @endif</span>
                <span class="text-slate-500">${{ number_format($reservationRoom->rate_cents / 100, 2) }}/night</span>
            </div>
        @endforeach
    </div>

    @if ($reservation->folio)
        <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-medium text-slate-800">Folio</h2>
                <a href="{{ route('folios.show', $reservation->folio) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">
                    View folio &rarr;
                </a>
            </div>
            <p class="text-sm text-slate-600">Balance: <span class="font-semibold">${{ number_format($reservation->folio->balance_cents / 100, 2) }}</span></p>
        </div>
    @endif

    @if ($reservation->special_requests)
        <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
            <h2 class="mb-2 font-medium text-slate-800">Special requests</h2>
            <p class="text-sm text-slate-600">{{ $reservation->special_requests }}</p>
        </div>
    @endif

    @can('cancel', $reservation)
        @if ($reservation->status->isActive() && $reservation->status->value !== 'checked_in')
            <div class="rounded-lg border border-red-200 bg-red-50 p-5">
                @if (! $showCancelForm)
                    <button wire:click="$set('showCancelForm', true)" class="text-sm font-medium text-red-700 hover:text-red-600">
                        Cancel this reservation
                    </button>
                @else
                    <form wire:submit="cancel" class="space-y-3">
                        <x-input-label for="cancelReason" value="Reason (optional)" />
                        <x-text-input id="cancelReason" type="text" wire:model="cancelReason" />
                        <div class="flex gap-3">
                            <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                                Confirm cancellation
                            </button>
                            <button type="button" wire:click="$set('showCancelForm', false)" class="text-sm text-slate-500 hover:text-slate-700">
                                Never mind
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    @endcan

    <div class="mt-6">
        <h2 class="mb-2 text-sm font-medium text-slate-500">History</h2>
        <div class="space-y-1 text-xs text-slate-500">
            @foreach ($reservation->statusLogs as $log)
                <p>{{ $log->created_at->format('M j, Y H:i') }} &mdash; {{ $log->from_status ?? 'created' }} &rarr; {{ $log->to_status }}
                    @if ($log->changedBy) by {{ $log->changedBy->name }} @endif
                </p>
            @endforeach
        </div>
    </div>
</div>
