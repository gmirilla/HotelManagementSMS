<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Payment Settings</h1>
        <p class="text-sm text-slate-500">Connect your own Paystack account so guests can pay online — money settles directly to you, not through this platform.</p>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-6 rounded-xl border border-slate-200/70 bg-white p-6 shadow-sm shadow-slate-900/5">
        <div>
            <x-input-label value="Paystack public key" />
            <x-text-input type="text" wire:model="publicKey" placeholder="pk_test_…" />
            <x-input-error :messages="$errors->get('publicKey')" />
        </div>

        <div>
            <x-input-label value="Paystack secret key" />
            <x-text-input type="password" wire:model="secretKey" :placeholder="$hasSecretKey ? 'A key is currently configured — leave blank to keep it' : 'sk_test_…'" />
            <p class="mt-1 text-xs text-slate-500">Never shown again once saved. Leaving this blank keeps whatever key is already configured.</p>
            <x-input-error :messages="$errors->get('secretKey')" />
        </div>

        <div class="border-t border-slate-100 pt-6">
            <x-primary-button class="w-auto">Save payment settings</x-primary-button>
        </div>
    </form>

    <div class="mt-6 max-w-2xl rounded-xl border border-slate-200/70 bg-white p-6 shadow-sm shadow-slate-900/5">
        <h2 class="font-medium text-slate-800">Webhook URL</h2>
        <p class="mt-1 text-sm text-slate-500">Paste this into your Paystack dashboard under Settings &rarr; API Keys &amp; Webhooks, so payments still confirm even if a guest closes their browser before returning here.</p>
        <code class="mt-3 block rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $webhookUrl }}</code>
    </div>
</div>
