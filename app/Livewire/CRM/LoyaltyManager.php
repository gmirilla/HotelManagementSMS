<?php

declare(strict_types=1);

namespace App\Livewire\CRM;

use App\Domain\CRM\Actions\EarnLoyaltyPointsAction;
use App\Domain\CRM\Actions\RedeemLoyaltyPointsAction;
use App\Domain\CRM\Support\LoyaltyBalanceCalculator;
use App\Models\Guest;
use App\Models\LoyaltyAccount;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Loyalty Program')]
class LoyaltyManager extends Component
{
    public string $guestSearch = '';

    public ?int $selectedGuestId = null;

    public bool $showEarnForm = false;

    public bool $showRedeemForm = false;

    public string $points = '';

    public string $description = '';

    #[Computed]
    public function guestResults(): Collection
    {
        if ($this->guestSearch === '') {
            return new Collection;
        }

        return Guest::where('tenant_id', auth()->user()->tenant_id)
            ->where(fn ($q) => $q->where('first_name', 'like', "%{$this->guestSearch}%")->orWhere('last_name', 'like', "%{$this->guestSearch}%"))
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedGuest(): ?Guest
    {
        return $this->selectedGuestId ? Guest::find($this->selectedGuestId) : null;
    }

    #[Computed]
    public function loyaltyAccount(): ?LoyaltyAccount
    {
        return $this->selectedGuestId ? LoyaltyAccount::where('guest_id', $this->selectedGuestId)->first() : null;
    }

    #[Computed]
    public function transactions(): Collection
    {
        if (! $this->loyaltyAccount) {
            return new Collection;
        }

        return $this->loyaltyAccount->transactions()->orderByDesc('transaction_date')->orderByDesc('id')->limit(25)->get();
    }

    public function selectGuest(int $guestId): void
    {
        $this->selectedGuestId = $guestId;
        $this->guestSearch = '';
    }

    public function startEarn(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('crm.manage'), 403);

        $this->reset(['points', 'description']);
        $this->showEarnForm = true;
        $this->showRedeemForm = false;
    }

    public function earn(EarnLoyaltyPointsAction $earnPoints): void
    {
        abort_unless(auth()->user()->hasPermissionTo('crm.manage'), 403);

        $this->validate([
            'points' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $earnPoints->handle($this->selectedGuest, (int) $this->points, $this->description);

        $this->showEarnForm = false;
        unset($this->loyaltyAccount, $this->transactions);
    }

    public function startRedeem(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('crm.manage'), 403);

        $this->reset(['points', 'description']);
        $this->showRedeemForm = true;
        $this->showEarnForm = false;
    }

    public function redeem(RedeemLoyaltyPointsAction $redeemPoints): void
    {
        abort_unless(auth()->user()->hasPermissionTo('crm.manage'), 403);

        $this->validate([
            'points' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $redeemPoints->handle($this->loyaltyAccount, (int) $this->points, $this->description);

        $this->showRedeemForm = false;
        unset($this->loyaltyAccount, $this->transactions);
    }

    public function render(LoyaltyBalanceCalculator $balanceCalculator)
    {
        return view('livewire.crm.loyalty-manager', [
            'pointsBalance' => $this->loyaltyAccount ? $balanceCalculator->pointsBalance($this->loyaltyAccount) : null,
            'tier' => $this->loyaltyAccount ? $balanceCalculator->tier($this->loyaltyAccount) : null,
        ]);
    }
}
