<x-layouts.guest title="Forgot password">
    <h1 class="mb-2 text-xl font-semibold text-slate-900">Forgot your password?</h1>
    <p class="mb-6 text-sm text-slate-600">Enter your email and we'll send you a link to reset it.</p>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button>Email password reset link</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Back to login</a>
    </p>
</x-layouts.guest>
