@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title — " : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900">
    <div class="min-h-full">
        <nav class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-sm">H</div>
                        <span class="font-semibold text-slate-800">{{ config('app.name') }}</span>
                    </a>

                    @auth
                        <div class="hidden items-center gap-4 text-sm text-slate-600 md:flex">
                            <a href="{{ route('front-desk.index') }}" class="hover:text-indigo-600">Front Desk</a>
                            <a href="{{ route('reservations.index') }}" class="hover:text-indigo-600">Reservations</a>
                            <a href="{{ route('guests.index') }}" class="hover:text-indigo-600">Guests</a>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 hover:text-indigo-600">
                                    Operations
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak class="absolute left-0 z-10 mt-2 w-48 rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                    <a href="{{ route('rooms.index') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Rooms</a>
                                    <a href="{{ route('room-types.index') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Room Types</a>
                                    <a href="{{ route('housekeeping.index') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Housekeeping</a>
                                    <a href="{{ route('maintenance.index') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Maintenance</a>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 hover:text-indigo-600">
                                    Restaurant &amp; Inventory
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak class="absolute left-0 z-10 mt-2 w-48 rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                    <a href="{{ route('restaurant.pos') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">POS</a>
                                    <a href="{{ route('restaurant.kitchen') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Kitchen Display</a>
                                    <a href="{{ route('restaurant.menu') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Menu</a>
                                    <a href="{{ route('inventory.index') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Inventory</a>
                                    <a href="{{ route('purchase-orders.index') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Purchase Orders</a>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 hover:text-indigo-600">
                                    Accounting
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak class="absolute left-0 z-10 mt-2 w-48 rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                    <a href="{{ route('accounting.chart-of-accounts') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Chart of Accounts</a>
                                    <a href="{{ route('accounting.journal-entries') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Journal Entries</a>
                                    <a href="{{ route('accounting.reports') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Financial Reports</a>
                                    <a href="{{ route('accounting.cashbook') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Cashbook</a>
                                    <a href="{{ route('accounting.ar-ap') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Receivables &amp; Payables</a>
                                    <a href="{{ route('accounting.tax-rules') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Tax Rules</a>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 hover:text-indigo-600">
                                    HR
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak class="absolute left-0 z-10 mt-2 w-48 rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                    <a href="{{ route('hr.employees') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Employees</a>
                                    <a href="{{ route('hr.attendance') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Attendance</a>
                                    <a href="{{ route('hr.leave') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Leave</a>
                                    <a href="{{ route('hr.payroll') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Payroll</a>
                                    <a href="{{ route('hr.performance-reviews') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Performance Reviews</a>
                                    <a href="{{ route('hr.disciplinary-records') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Disciplinary Records</a>
                                    <a href="{{ route('hr.recruitment') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Recruitment</a>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 hover:text-indigo-600">
                                    CRM &amp; Events
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak class="absolute left-0 z-10 mt-2 w-52 rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                    <a href="{{ route('crm.corporate-accounts') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Corporate Accounts</a>
                                    <a href="{{ route('crm.feedback') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Guest Feedback</a>
                                    <a href="{{ route('crm.loyalty') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Loyalty Program</a>
                                    <a href="{{ route('crm.coupons') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Coupons</a>
                                    <a href="{{ route('crm.campaigns') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Marketing Campaigns</a>
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <a href="{{ route('events.spaces') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Event Spaces</a>
                                    <a href="{{ route('events.bookings') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Event Bookings</a>
                                </div>
                            </div>
                        </div>

                        @if (auth()->user()->currentBranch)
                            <span class="hidden rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 lg:inline-block">
                                {{ auth()->user()->currentBranch->name }}
                            </span>
                        @endif
                    @endauth
                </div>

                @auth
                    <div class="flex items-center gap-4 text-sm">
                        <a href="{{ route('mfa.setup') }}" class="text-slate-600 hover:text-slate-900">Security</a>
                        <a href="{{ route('sessions.index') }}" class="text-slate-600 hover:text-slate-900">Sessions</a>
                        <span class="text-slate-400">|</span>
                        <span class="text-slate-700">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-slate-100 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-200">
                                Log out
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </nav>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
