<x-layouts.app title="Recovery codes">
    <div class="mx-auto max-w-xl">
        <h1 class="mb-2 text-xl font-semibold text-slate-900">Save your recovery codes</h1>
        <p class="mb-6 text-sm text-slate-600">
            Store these somewhere safe. Each code can be used once to sign in if you lose access to your authenticator
            app. They will not be shown again.
        </p>

        <div class="mb-6 grid grid-cols-2 gap-2 rounded-lg border border-slate-200 bg-slate-50 p-6 font-mono text-sm">
            @foreach ($recoveryCodes as $code)
                <div>{{ $code }}</div>
            @endforeach
        </div>

        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">
            Continue to dashboard &rarr;
        </a>
    </div>
</x-layouts.app>
