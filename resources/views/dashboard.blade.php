<x-layouts.app title="Dashboard">
    <h1 class="mb-2 text-xl font-semibold text-slate-900">Welcome back, {{ auth()->user()->name }}</h1>
    <p class="mb-8 text-sm text-slate-600">
        This is a placeholder landing page — the full occupancy/revenue/KPI dashboard (SRS §3.17, FR-RPT-005) is
        built alongside the reporting module.
    </p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('mfa.setup') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Two-factor authentication</h2>
            <p class="mt-1 text-sm text-slate-500">Add an extra layer of security to your account.</p>
        </a>

        <a href="{{ route('password.edit') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Password</h2>
            <p class="mt-1 text-sm text-slate-500">Change your account password.</p>
        </a>

        <a href="{{ route('sessions.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Active sessions</h2>
            <p class="mt-1 text-sm text-slate-500">See and revoke devices signed in to your account.</p>
        </a>
    </div>
</x-layouts.app>
