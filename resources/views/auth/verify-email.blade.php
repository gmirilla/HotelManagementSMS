<x-layouts.guest title="Verify email">
    <h1 class="mb-2 text-xl font-semibold text-slate-900">Verify your email address</h1>
    <p class="mb-6 text-sm text-slate-600">
        Thanks for signing up! Before getting started, check your email for a verification link.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>Resend verification email</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-700">Log out</button>
        </form>
    </div>
</x-layouts.guest>
