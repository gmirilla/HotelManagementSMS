<?php

declare(strict_types=1);

namespace App\Livewire\FrontDesk;

use App\Domain\FrontDesk\Actions\PostFolioChargeAction;
use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\Payment\Actions\RecordFolioPaymentAction;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Models\Folio;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Folio')]
class FolioShow extends Component
{
    public Folio $folio;

    public bool $showChargeForm = false;

    public string $chargeType = 'misc';

    public string $chargeDescription = '';

    public string $chargeAmount = '';

    public bool $showPaymentForm = false;

    public string $paymentMethod = 'cash';

    public string $paymentAmount = '';

    public function mount(Folio $folio): void
    {
        $this->authorize('view', $folio);
        $this->folio = $folio;
    }

    public function addCharge(PostFolioChargeAction $postFolioCharge): void
    {
        $this->authorize('update', $this->folio);

        $this->validate([
            'chargeType' => ['required', 'string'],
            'chargeDescription' => ['required', 'string', 'max:255'],
            'chargeAmount' => ['required', 'numeric'],
        ]);

        $postFolioCharge->handle(
            $this->folio,
            $this->chargeType,
            $this->chargeDescription,
            (int) round(((float) $this->chargeAmount) * 100),
            auth()->user(),
        );

        $this->folio->refresh();
        $this->reset(['chargeDescription', 'chargeAmount']);
        $this->showChargeForm = false;
    }

    public function addPayment(RecordFolioPaymentAction $recordPayment): void
    {
        $this->authorize('update', $this->folio);

        $this->validate([
            'paymentMethod' => ['required', 'string'],
            'paymentAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $recordPayment->handle(
            $this->folio,
            $this->paymentMethod,
            (int) round(((float) $this->paymentAmount) * 100),
            auth()->user(),
        );

        $this->folio->refresh();
        $this->reset(['paymentAmount']);
        $this->showPaymentForm = false;
    }

    public function render()
    {
        $this->folio->load(['guest', 'branch', 'charges' => fn ($q) => $q->orderByDesc('charge_date'), 'payments' => fn ($q) => $q->orderByDesc('created_at')]);

        return view('livewire.front-desk.folio-show', [
            'chargeTypes' => ChargeType::cases(),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }
}
