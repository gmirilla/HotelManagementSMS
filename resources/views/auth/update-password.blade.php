<x-layouts.app title="Change password">
    <div class="mx-auto max-w-xl">
        <h1 class="mb-6 text-xl font-semibold text-slate-900">Change your password</h1>

        @if (session('status') === 'password-expired')
            <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Your password has expired and must be changed before you can continue.
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5 rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="current_password" value="Current password" />
                <x-text-input id="current_password" type="password" name="current_password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('current_password')" />
            </div>

            <div>
                <x-input-label for="password" value="New password" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirm new password" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            <x-primary-button class="w-auto">Update password</x-primary-button>
        </form>
    </div>
</x-layouts.app>
