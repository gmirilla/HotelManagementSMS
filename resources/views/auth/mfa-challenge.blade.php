<x-layouts.guest title="Verify your identity">
    <h1 class="mb-2 text-xl font-semibold text-slate-900">Two-factor verification</h1>
    <p class="mb-6 text-sm text-slate-600">
        Enter the 6-digit code from your authenticator app, or one of your recovery codes.
    </p>

    <form method="POST" action="{{ route('mfa.challenge.verify') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="code" value="Authentication code" />
            <x-text-input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus />
            <x-input-error :messages="$errors->get('code')" />
        </div>

        <x-primary-button>Verify</x-primary-button>
    </form>
</x-layouts.guest>
