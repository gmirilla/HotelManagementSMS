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

        <a href="{{ route('housekeeping.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Housekeeping</h2>
            <p class="mt-1 text-sm text-slate-500">Today's cleaning tasks and inspections.</p>
        </a>

        <a href="{{ route('maintenance.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Maintenance</h2>
            <p class="mt-1 text-sm text-slate-500">Work orders, assets, and repairs.</p>
        </a>
    </div>

    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Restaurant &amp; Inventory</h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('restaurant.pos') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Point of Sale</h2>
            <p class="mt-1 text-sm text-slate-500">Take orders for tables and room service.</p>
        </a>

        <a href="{{ route('restaurant.kitchen') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Kitchen Display</h2>
            <p class="mt-1 text-sm text-slate-500">Live queue of tickets sent to the kitchen.</p>
        </a>

        <a href="{{ route('restaurant.menu') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Restaurant &amp; Menu</h2>
            <p class="mt-1 text-sm text-slate-500">Outlets, tables, categories, and menu items.</p>
        </a>

        <a href="{{ route('inventory.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Inventory</h2>
            <p class="mt-1 text-sm text-slate-500">Stock levels, receipts, wastage, and adjustments.</p>
        </a>

        <a href="{{ route('purchase-orders.index') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Purchase Orders</h2>
            <p class="mt-1 text-sm text-slate-500">Order from suppliers and receive goods.</p>
        </a>
    </div>

    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Accounting</h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('accounting.chart-of-accounts') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Chart of Accounts</h2>
            <p class="mt-1 text-sm text-slate-500">Assets, liabilities, equity, revenue, and expense accounts.</p>
        </a>

        <a href="{{ route('accounting.journal-entries') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Journal Entries</h2>
            <p class="mt-1 text-sm text-slate-500">Post balanced double-entry journal entries.</p>
        </a>

        <a href="{{ route('accounting.reports') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Financial Reports</h2>
            <p class="mt-1 text-sm text-slate-500">Trial balance, profit &amp; loss, and balance sheet.</p>
        </a>

        <a href="{{ route('accounting.cashbook') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Cashbook</h2>
            <p class="mt-1 text-sm text-slate-500">Cash in/out per shift with running balances.</p>
        </a>

        <a href="{{ route('accounting.ar-ap') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Receivables &amp; Payables</h2>
            <p class="mt-1 text-sm text-slate-500">Track and settle corporate and supplier balances.</p>
        </a>

        <a href="{{ route('accounting.tax-rules') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Tax Rules</h2>
            <p class="mt-1 text-sm text-slate-500">Configure tax rates applied to sales.</p>
        </a>
    </div>

    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Human Resources</h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('hr.employees') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Employees</h2>
            <p class="mt-1 text-sm text-slate-500">Employee records, roles, and employment status.</p>
        </a>

        <a href="{{ route('hr.attendance') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Attendance</h2>
            <p class="mt-1 text-sm text-slate-500">Clock in/out and daily attendance status.</p>
        </a>

        <a href="{{ route('hr.leave') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Leave</h2>
            <p class="mt-1 text-sm text-slate-500">Leave requests, approvals, and balances.</p>
        </a>

        <a href="{{ route('hr.payroll') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Payroll</h2>
            <p class="mt-1 text-sm text-slate-500">Process payroll runs and view payslips.</p>
        </a>

        <a href="{{ route('hr.performance-reviews') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Performance Reviews</h2>
            <p class="mt-1 text-sm text-slate-500">Periodic reviews with restricted visibility.</p>
        </a>

        <a href="{{ route('hr.disciplinary-records') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Disciplinary Records</h2>
            <p class="mt-1 text-sm text-slate-500">Incident records with restricted visibility.</p>
        </a>

        <a href="{{ route('hr.recruitment') }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-300 hover:shadow-sm">
            <h2 class="font-medium text-slate-800">Recruitment</h2>
            <p class="mt-1 text-sm text-slate-500">Job openings and candidate pipeline.</p>
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
