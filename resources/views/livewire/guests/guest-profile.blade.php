<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">{{ $guest->fullName() }}</h1>
            <p class="text-sm text-slate-500">{{ $guest->email }} &middot; {{ $guest->phone }}</p>
        </div>
        <a href="{{ route('guests.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">&larr; All guests</a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="mb-3 font-medium text-slate-800">Recent reservations</h2>
                <div class="divide-y divide-slate-100">
                    @forelse ($guest->reservations as $reservation)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <div>
                                <a href="{{ route('reservations.show', $reservation) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                    {{ $reservation->confirmation_code }}
                                </a>
                                <span class="ml-2 text-slate-500">{{ $reservation->arrival_date->format('M j, Y') }} &ndash; {{ $reservation->departure_date->format('M j, Y') }}</span>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $reservation->status->value }}</span>
                        </div>
                    @empty
                        <p class="py-2 text-sm text-slate-500">No reservations yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="mb-3 font-medium text-slate-800">Notes</h2>

                <form wire:submit="addNote" class="mb-4 flex gap-2">
                    <x-text-input type="text" wire:model="note" placeholder="Add a note&hellip;" class="mt-0 flex-1" />
                    <x-primary-button class="w-auto">Add</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('note')" />

                <div class="space-y-2">
                    @forelse ($guest->notes as $note)
                        <div class="rounded-md {{ $note->is_alert ? 'bg-amber-50 text-amber-800' : 'bg-slate-50 text-slate-700' }} p-3 text-sm">
                            {{ $note->note }}
                            <span class="ml-2 text-xs opacity-70">&mdash; {{ $note->createdBy?->name ?? 'System' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No notes yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="mb-3 font-medium text-slate-800">Documents</h2>
                @forelse ($guest->documents as $document)
                    <div class="mb-2 text-sm">
                        <p class="font-medium text-slate-700">{{ ucfirst(str_replace('_', ' ', $document->document_type->value)) }}</p>
                        <p class="text-slate-500">{{ $document->issuing_country }} &middot; expires {{ $document->expires_on?->format('M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No documents on file.</p>
                @endforelse
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="mb-3 font-medium text-slate-800">Emergency / family contacts</h2>
                @forelse ($guest->contacts as $contact)
                    <div class="mb-2 text-sm">
                        <p class="font-medium text-slate-700">{{ $contact->name }}</p>
                        <p class="text-slate-500">{{ $contact->relationship }} &middot; {{ $contact->phone }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No contacts on file.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
