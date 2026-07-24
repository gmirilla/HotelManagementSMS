<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Event Bookings</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        @can('events.manage')
            <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                New booking
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6 sm:grid-cols-3">
            <div>
                <x-input-label value="Event space" />
                <select wire:model="eventSpaceId" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">Select…</option>
                    @foreach ($this->spaces as $space)
                        <option value="{{ $space->id }}">{{ $space->name }} ({{ $space->capacity }} pax)</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('eventSpaceId')" />
            </div>
            <div>
                <x-input-label value="Title" />
                <x-text-input type="text" wire:model="title" />
                <x-input-error :messages="$errors->get('title')" />
            </div>
            <div>
                <x-input-label value="Event type" />
                <select wire:model="eventType" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="conference">Conference</option>
                    <option value="wedding">Wedding</option>
                    <option value="banquet">Banquet</option>
                </select>
            </div>
            <div>
                <x-input-label value="Starts at" />
                <x-text-input type="datetime-local" wire:model="startAt" />
                <x-input-error :messages="$errors->get('startAt')" />
            </div>
            <div>
                <x-input-label value="Ends at" />
                <x-text-input type="datetime-local" wire:model="endAt" />
                <x-input-error :messages="$errors->get('endAt')" />
            </div>
            <div>
                <x-input-label value="Attendee count" />
                <x-text-input type="number" min="1" wire:model="attendeeCount" />
                <x-input-error :messages="$errors->get('attendeeCount')" />
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 lg:col-span-1">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Booking</th><th class="px-4 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->bookings as $booking)
                        <tr wire:key="booking-{{ $booking->id }}" wire:click="select({{ $booking->id }})" @class(['cursor-pointer hover:bg-slate-50', 'bg-brand-50' => $selectedBookingId === $booking->id])>
                            <td class="px-4 py-2">
                                <p class="font-medium text-slate-800">{{ $booking->title }}</p>
                                <p class="text-xs text-slate-500">{{ $booking->eventSpace->name }} — {{ $booking->start_at->format('M j, g:ia') }}</p>
                            </td>
                            <td class="px-4 py-2">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst($booking->status->value) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-center text-slate-500">No event bookings yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="lg:col-span-2">
            @if ($this->selectedBooking)
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-800">{{ $this->selectedBooking->title }}</p>
                        <p class="text-sm text-slate-500">
                            {{ $this->selectedBooking->eventSpace->name }} ·
                            {{ $this->selectedBooking->start_at->format('M j, Y g:ia') }} – {{ $this->selectedBooking->end_at->format('g:ia') }}
                            ({{ $this->selectedBooking->durationHours() }}h)
                        </p>
                    </div>
                    @can('events.manage')
                        <div class="flex gap-3">
                            @if ($this->selectedBooking->status->value === 'tentative')
                                <button wire:click="confirm" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">Confirm</button>
                            @endif
                            @if (! in_array($this->selectedBooking->status->value, ['cancelled', 'completed']))
                                <button wire:click="cancel" wire:confirm="Cancel this booking?" class="text-sm font-medium text-red-600 hover:text-red-500">Cancel</button>
                            @endif
                        </div>
                    @endcan
                </div>

                @can('events.manage')
                    <form wire:submit="addItem" class="mb-4 flex items-end gap-3 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4">
                        <div class="flex-1">
                            <x-input-label value="Service" />
                            <select wire:model="selectedServiceId" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                                <option value="">Select…</option>
                                @foreach ($this->services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }} (${{ number_format($service->unit_price_cents / 100, 2) }}/{{ str_replace('_', ' ', $service->unit) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-24">
                            <x-input-label value="Qty" />
                            <x-text-input type="number" min="1" wire:model="itemQuantity" />
                        </div>
                        <x-primary-button class="w-auto">Add</x-primary-button>
                    </form>
                    <x-input-error :messages="$errors->get('selectedServiceId')" />
                @endcan

                @if ($bill)
                    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-3">Line item</th><th class="px-4 py-3">Amount</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="px-4 py-2 text-slate-800">Venue rental ({{ $this->selectedBooking->durationHours() }}h)</td>
                                    <td class="px-4 py-2 text-slate-600">${{ number_format($bill['venue_cents'] / 100, 2) }}</td>
                                </tr>
                                @foreach ($bill['items'] as $row)
                                    <tr wire:key="bill-item-{{ $row['item']->id }}">
                                        <td class="px-4 py-2 text-slate-800">{{ $row['item']->eventService->name }} × {{ $row['item']->quantity }}</td>
                                        <td class="px-4 py-2 text-slate-600">${{ number_format($row['line_total_cents'] / 100, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-slate-50 font-semibold">
                                    <td class="px-4 py-2 text-slate-800">Total</td>
                                    <td class="px-4 py-2 text-slate-800">${{ number_format($bill['total_cents'] / 100, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <p class="text-sm text-slate-500">Select a booking to view its consolidated bill.</p>
            @endif
        </div>
    </div>
</div>
