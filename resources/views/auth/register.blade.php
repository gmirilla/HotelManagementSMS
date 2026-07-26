<x-layouts.guest title="Create account" :split="true">
    <h1 class="text-2xl font-semibold text-slate-900">Create your account</h1>
    <p class="mt-1 mb-6 text-sm text-slate-500">Set up guest access to manage your bookings.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Full name" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
            <p class="mt-1 text-xs text-slate-500">At least 10 characters, with upper &amp; lower case, a number, and a symbol.</p>
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-primary-button>Create account</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">Log in</a>
    </p>
</x-layouts.guest>
