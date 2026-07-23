<x-layouts.app title="Dashboard">
    <h1 class="mb-2 text-xl font-semibold text-slate-900">Welcome back, {{ auth()->user()->name }}</h1>
    <p class="mb-8 text-sm text-slate-600">
        This is a placeholder landing page — the full occupancy/revenue/KPI dashboard (SRS §3.17, FR-RPT-005) is
        built alongside the reporting module.
    </p>

    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Operations</h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('front-desk.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Front Desk</h2>
            <p class="mt-1 text-sm text-slate-500">Today's arrivals, departures, and in-house guests.</p>
        </a>

        <a href="{{ route('reservations.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Reservations</h2>
            <p class="mt-1 text-sm text-slate-500">Search, filter, and create new bookings.</p>
        </a>

        <a href="{{ route('guests.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Guests</h2>
            <p class="mt-1 text-sm text-slate-500">Guest profiles, documents, and stay history.</p>
        </a>

        <a href="{{ route('rooms.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Rooms</h2>
            <p class="mt-1 text-sm text-slate-500">Live room status board for this branch.</p>
        </a>

        <a href="{{ route('room-types.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Room Types</h2>
            <p class="mt-1 text-sm text-slate-500">Rates, capacity, and amenities per room type.</p>
        </a>
    </div>

    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Account</h2>
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
