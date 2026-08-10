<?php

declare(strict_types=1);

namespace App\Livewire\Guests;

use App\Domain\Guest\Actions\CreateGuestAction;
use App\Domain\Guest\Enums\GuestFlag;
use App\Models\Guest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Guests')]
class GuestManager extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $blacklistedOnly = false;

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $nationality = '';

    public ?int $blacklistingGuestId = null;

    public string $blacklistReason = '';

    protected function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBlacklistedOnly(): void
    {
        $this->resetPage();
    }

    public function guests(): LengthAwarePaginator
    {
        $user = auth()->user();

        return Guest::query()
            ->where('tenant_id', $user->tenant_id)
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->blacklistedOnly, fn ($query) => $query->where('flag', GuestFlag::Blacklisted))
            ->orderBy('last_name')
            ->paginate(15);
    }

    public function create(): void
    {
        $this->authorize('create', Guest::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $guestId): void
    {
        $guest = Guest::findOrFail($guestId);
        $this->authorize('update', $guest);

        $this->editingId = $guest->id;
        $this->firstName = $guest->first_name;
        $this->lastName = $guest->last_name;
        $this->email = (string) $guest->email;
        $this->phone = (string) $guest->phone;
        $this->nationality = (string) $guest->nationality;
        $this->showForm = true;
    }

    public function save(CreateGuestAction $createGuest): void
    {
        $this->validate();

        if ($this->editingId) {
            $guest = Guest::findOrFail($this->editingId);
            $this->authorize('update', $guest);

            $guest->fill([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email ?: null,
                'phone' => $this->phone ?: null,
                'nationality' => $this->nationality ?: null,
            ])->save();
        } else {
            $this->authorize('create', Guest::class);

            $createGuest->handle(
                tenantId: auth()->user()->tenant_id,
                firstName: $this->firstName,
                lastName: $this->lastName,
                email: $this->email ?: null,
                phone: $this->phone ?: null,
                nationality: $this->nationality ?: null,
            );
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function toggleVip(int $guestId): void
    {
        $guest = Guest::findOrFail($guestId);
        $this->authorize('update', $guest);

        $guest->update(['flag' => $guest->flag === GuestFlag::Vip ? GuestFlag::None : GuestFlag::Vip]);
    }

    public function startBlacklist(int $guestId): void
    {
        $guest = Guest::findOrFail($guestId);
        $this->authorize('blacklist', $guest);

        $this->blacklistingGuestId = $guestId;
        $this->blacklistReason = '';
    }

    public function cancelBlacklist(): void
    {
        $this->blacklistingGuestId = null;
        $this->blacklistReason = '';
    }

    public function confirmBlacklist(): void
    {
        $guest = Guest::findOrFail($this->blacklistingGuestId);
        $this->authorize('blacklist', $guest);

        $this->validate(['blacklistReason' => ['required', 'string', 'max:500']]);

        $guest->update(['flag' => GuestFlag::Blacklisted, 'blacklist_reason' => $this->blacklistReason]);

        $this->cancelBlacklist();
    }

    public function removeFromBlacklist(int $guestId): void
    {
        $guest = Guest::findOrFail($guestId);
        $this->authorize('blacklist', $guest);

        $guest->update(['flag' => GuestFlag::None, 'blacklist_reason' => null]);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'firstName', 'lastName', 'email', 'phone', 'nationality']);
    }

    public function render()
    {
        return view('livewire.guests.guest-manager', ['guests' => $this->guests()]);
    }
}
