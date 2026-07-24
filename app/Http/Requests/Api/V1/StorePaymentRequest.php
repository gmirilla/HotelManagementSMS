<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $folio = $this->route('folio');

        return $this->user()->canAccessBranch($folio->branch_id) && $this->user()->hasPermissionTo('payments.process');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Gateway methods (stripe/paypal/flutterwave/paystack) are
            // deliberately excluded — those are webhook-driven and never
            // recorded synchronously via this endpoint (FR-PAY-001/004).
            'method' => ['required', 'string', 'in:cash,pos_terminal,bank_transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
