<x-layouts.app title="Two-factor authentication">
    <div class="mx-auto max-w-xl">
        <h1 class="mb-6 text-xl font-semibold text-slate-900">Two-factor authentication</h1>

        @if ($enabled)
            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <p class="mb-4 text-sm text-slate-700">
                    Two-factor authentication is <span class="font-medium text-emerald-700">enabled</span> on your account.
                </p>
                <form method="POST" action="{{ route('mfa.disable') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                        Disable two-factor authentication
                    </button>
                </form>
            </div>
        @else
            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <p class="mb-4 text-sm text-slate-700">
                    Scan this into your authenticator app (Google Authenticator, 1Password, Authy, etc.), or enter the key manually:
                </p>

                <div class="mb-4 rounded-md bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Manual entry key</p>
                    <p class="mt-1 break-all font-mono text-sm text-slate-800">{{ $secret }}</p>
                </div>

                <div class="mb-6 rounded-md bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">otpauth URI</p>
                    <p class="mt-1 break-all font-mono text-xs text-slate-600">{{ $otpAuthUri }}</p>
                </div>

                <form method="POST" action="{{ route('mfa.confirm') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="code" value="Enter the 6-digit code from your app to confirm" />
                        <x-text-input id="code" type="text" name="code" inputmode="numeric" required autofocus />
                        <x-input-error :messages="$errors->get('code')" />
                    </div>

                    <x-primary-button>Enable two-factor authentication</x-primary-button>
                </form>
            </div>
        @endif
    </div>
</x-layouts.app>
