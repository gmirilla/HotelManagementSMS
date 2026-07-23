<?php

declare(strict_types=1);

namespace App\Livewire\Guests;

use App\Models\Guest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Guest profile')]
class GuestProfile extends Component
{
    public Guest $guest;

    public string $note = '';

    public function mount(Guest $guest): void
    {
        $this->authorize('view', $guest);
        $this->guest = $guest;
    }

    public function addNote(): void
    {
        $this->authorize('update', $this->guest);
        $this->validate(['note' => ['required', 'string', 'max:2000']]);

        $this->guest->notes()->create([
            'created_by_user_id' => auth()->id(),
            'note' => $this->note,
        ]);

        $this->reset('note');
    }

    public function render()
    {
        $this->guest->load(['documents', 'contacts', 'notes.createdBy', 'reservations' => fn ($query) => $query->latest('arrival_date')->limit(10)]);

        return view('livewire.guests.guest-profile');
    }
}
