<x-layouts.guest title="Log in">
    <h1 class="mb-6 text-xl font-semibold text-slate-900">Log in to your account</h1>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500">
                Remember me
            </label>

            <a href="{{ route('password.request') }}" class="text-sm text-brand-600 hover:text-brand-500">
                Forgot your password?
            </a>
        </div>

        <x-primary-button>Log in</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Booking a stay?
        <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:text-brand-500">Create a guest account</a>
    </p>
</x-layouts.guest>
