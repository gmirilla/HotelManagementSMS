<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Settings\Actions\UpdatePaymentSettingsAction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payment Settings')]
class PaymentSettings extends Component
{
    public string $publicKey = '';

    /**
     * Write-only: never populated from the stored (encrypted) value — see
     * UpdatePaymentSettingsAction. hasSecretKey drives the "a key is
     * currently configured" indicator in the view instead.
     */
    public string $secretKey = '';

    public bool $hasSecretKey = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('settings.manage'), 403);
        abort_if(auth()->user()->tenant === null, 422);

        $tenant = auth()->user()->tenant;
        $this->publicKey = $tenant->paystack_public_key ?? '';
        $this->hasSecretKey = $tenant->paystack_secret_key !== null;
    }

    public function save(UpdatePaymentSettingsAction $updatePaymentSettings): void
    {
        abort_unless(auth()->user()->hasPermissionTo('settings.manage'), 403);

        $this->validate([
            'publicKey' => ['nullable', 'string', 'max:255'],
            'secretKey' => ['nullable', 'string', 'max:255'],
        ]);

        $updatePaymentSettings->handle(
            auth()->user()->tenant,
            $this->publicKey ?: null,
            $this->secretKey ?: null,
        );

        session()->flash('status', __('Payment settings updated.'));

        $this->redirect(route('admin.payment-settings'));
    }

    public function render()
    {
        return view('livewire.admin.payment-settings', [
            'webhookUrl' => route('webhooks.paystack'),
        ]);
    }
}
