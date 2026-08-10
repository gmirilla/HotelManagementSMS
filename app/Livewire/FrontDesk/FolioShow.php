<?php

declare(strict_types=1);

namespace App\Livewire\FrontDesk;

use App\Domain\FrontDesk\Actions\PostFolioChargeAction;
use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\Payment\Actions\ConfirmGatewayPaymentAction;
use App\Domain\Payment\Actions\InitiateGatewayPaymentAction;
use App\Domain\Payment\Actions\RecordFolioPaymentAction;
use App\Domain\Payment\Actions\RefundGatewayPaymentAction;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Models\Folio;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public string $payOnlineAmount = '';

    /**
     * Paystack appends this to the callback URL on redirect — see
     * InitiateGatewayPaymentAction's $callbackUrl. Only ever used as a
     * trigger to re-check with Paystack, never trusted on its own.
     */
    #[Url]
    public ?string $reference = null;

    public function mount(Folio $folio, ConfirmGatewayPaymentAction $confirmPayment): void
    {
        $this->authorize('view', $folio);
        $this->folio = $folio;
        $this->payOnlineAmount = number_format($folio->balance_cents / 100, 2, '.', '');

        if ($this->reference !== null) {
            try {
                $confirmPayment->handle($this->reference);
                $this->folio->refresh();
            } catch (ModelNotFoundException) {
                // A stale or tampered reference — nothing to confirm, and
                // the webhook (if this was ever a real transaction) will
                // have already settled the actual Payment row regardless.
            }

            $this->reference = null;
        }
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

    public function payWithPaystack(InitiateGatewayPaymentAction $initiatePayment): void
    {
        $this->authorize('processGatewayPayment', $this->folio);

        $this->validate(['payOnlineAmount' => ['required', 'numeric', 'min:0.01']]);

        $result = $initiatePayment->handle(
            $this->folio,
            (int) round(((float) $this->payOnlineAmount) * 100),
            route('folios.show', $this->folio),
            auth()->user(),
        );

        $this->redirect($result->authorizationUrl, navigate: false);
    }

    public function refundPayment(int $paymentId, RefundGatewayPaymentAction $refundPayment): void
    {
        $payment = Payment::findOrFail($paymentId);
        $this->authorize('refund', $payment);

        $refundPayment->handle($payment, __('Refunded by :name via Front Desk', ['name' => auth()->user()->name]), auth()->user());

        $this->folio->refresh();
    }

    public function downloadReceipt(): StreamedResponse
    {
        $this->authorize('view', $this->folio);

        $this->folio->load(['charges', 'payments', 'guest', 'reservation', 'branch']);

        $pdf = Pdf::loadView('pdf.folio-receipt', ['folio' => $this->folio]);

        // Livewire's file-download support only intercepts a StreamedResponse
        // or BinaryFileResponse from a component method — DomPDF's own
        // ->download() returns a plain Illuminate\Http\Response, which
        // Livewire doesn't recognize as a download and tries to serialize
        // as a normal component response instead, corrupting the binary PDF.
        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf->output();
            },
            "receipt-{$this->folio->id}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function render()
    {
        $this->folio->load(['guest', 'branch', 'charges' => fn ($q) => $q->orderByDesc('charge_date'), 'payments' => fn ($q) => $q->orderByDesc('created_at')]);

        return view('livewire.front-desk.folio-show', [
            'chargeTypes' => ChargeType::cases(),
            'paymentMethods' => PaymentMethod::manual(),
        ]);
    }
}
