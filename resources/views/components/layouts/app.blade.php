@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title — " : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>{!! \App\Support\Theme\BrandPalette::cssVariables(auth()->user()?->tenant?->brand_color) !!}</style>
</head>
<body class="h-full font-sans antialiased text-slate-900">
    <div class="min-h-full">
        <nav class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/85 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-sm shadow-brand-600/30">H</div>
                        <span class="font-semibold text-slate-800">{{ config('app.name') }}</span>
                    </a>

                    @auth
                        <div class="hidden items-center gap-1 text-sm font-medium text-slate-600 md:flex">
                            <a href="{{ route('front-desk.index') }}" class="rounded-md px-2 py-1 transition hover:bg-brand-50 hover:text-brand-600">Front Desk</a>
                            <a href="{{ route('reservations.index') }}" class="rounded-md px-2 py-1 transition hover:bg-brand-50 hover:text-brand-600">Reservations</a>
                            <a href="{{ route('guests.index') }}" class="rounded-md px-2 py-1 transition hover:bg-brand-50 hover:text-brand-600">Guests</a>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 rounded-md px-2 py-1 transition hover:bg-brand-50 hover:text-brand-600">
                                    Operations
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 z-10 mt-2 w-48 rounded-xl border border-slate-200/70 bg-white py-1 shadow-xl shadow-slate-900/10">
                                    <a href="{{ route('rooms.index') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Rooms</a>
                                    <a href="{{ route('room-types.index') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Room Types</a>
                                    <a href="{{ route('housekeeping.index') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Housekeeping</a>
                                    <a href="{{ route('maintenance.index') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Maintenance</a>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 rounded-md px-2 py-1 transition hover:bg-brand-50 hover:text-brand-600">
                                    Restaurant &amp; Inventory
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 z-10 mt-2 w-48 rounded-xl border border-slate-200/70 bg-white py-1 shadow-xl shadow-slate-900/10">
                                    <a href="{{ route('restaurant.pos') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">POS</a>
                                    <a href="{{ route('restaurant.kitchen') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Kitchen Display</a>
                                    <a href="{{ route('restaurant.menu') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Menu</a>
                                    <a href="{{ route('inventory.index') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Inventory</a>
                                    <a href="{{ route('purchase-orders.index') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Purchase Orders</a>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 rounded-md px-2 py-1 transition hover:bg-brand-50 hover:text-brand-600">
                                    Accounting
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 z-10 mt-2 w-48 rounded-xl border border-slate-200/70 bg-white py-1 shadow-xl shadow-slate-900/10">
                                    <a href="{{ route('accounting.chart-of-accounts') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Chart of Accounts</a>
                                    <a href="{{ route('accounting.journal-entries') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Journal Entries</a>
                                    <a href="{{ route('accounting.reports') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Financial Reports</a>
                                    <a href="{{ route('accounting.cashbook') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Cashbook</a>
                                    <a href="{{ route('accounting.ar-ap') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Receivables &amp; Payables</a>
                                    <a href="{{ route('accounting.tax-rules') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Tax Rules</a>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 rounded-md px-2 py-1 transition hover:bg-brand-50 hover:text-brand-600">
                                    HR
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 z-10 mt-2 w-48 rounded-xl border border-slate-200/70 bg-white py-1 shadow-xl shadow-slate-900/10">
                                    <a href="{{ route('hr.employees') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Employees</a>
                                    <a href="{{ route('hr.attendance') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Attendance</a>
                                    <a href="{{ route('hr.leave') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Leave</a>
                                    <a href="{{ route('hr.payroll') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Payroll</a>
                                    <a href="{{ route('hr.performance-reviews') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Performance Reviews</a>
                                    <a href="{{ route('hr.disciplinary-records') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Disciplinary Records</a>
                                    <a href="{{ route('hr.recruitment') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Recruitment</a>
                                </div>
                            </div>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 rounded-md px-2 py-1 transition hover:bg-brand-50 hover:text-brand-600">
                                    CRM &amp; Events
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 z-10 mt-2 w-52 rounded-xl border border-slate-200/70 bg-white py-1 shadow-xl shadow-slate-900/10">
                                    <a href="{{ route('crm.corporate-accounts') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Corporate Accounts</a>
                                    <a href="{{ route('crm.feedback') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Guest Feedback</a>
                                    <a href="{{ route('crm.loyalty') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Loyalty Program</a>
                                    <a href="{{ route('crm.coupons') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Coupons</a>
                                    <a href="{{ route('crm.campaigns') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Marketing Campaigns</a>
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <a href="{{ route('events.spaces') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Event Spaces</a>
                                    <a href="{{ route('events.bookings') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Event Bookings</a>
                                </div>
                            </div>

                            @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('settings.manage'))
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = ! open" @click.outside="open = false" class="flex items-center gap-1 rounded-md px-2 py-1 transition hover:bg-brand-50 hover:text-brand-600">
                                        Administration
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                    <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 z-10 mt-2 w-48 rounded-xl border border-slate-200/70 bg-white py-1 shadow-xl shadow-slate-900/10">
                                        @can('viewAny', App\Models\User::class)
                                            <a href="{{ route('admin.users') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Users</a>
                                        @endcan
                                        @if (auth()->user()->can('settings.manage'))
                                            <a href="{{ route('admin.appearance') }}" class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">Appearance</a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if (auth()->user()->currentBranch)
                            <span class="hidden items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-600/10 lg:inline-flex">
                                <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                {{ auth()->user()->currentBranch->name }}
                            </span>
                        @endif
                    @endauth
                </div>

                @auth
                    <div class="flex items-center gap-4 text-sm">
                        <a href="{{ route('mfa.setup') }}" class="text-slate-600 hover:text-brand-600">Security</a>
                        <a href="{{ route('sessions.index') }}" class="text-slate-600 hover:text-brand-600">Sessions</a>
                        <span class="text-slate-300">|</span>
                        <span class="font-medium text-slate-700">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-slate-100 px-3 py-1.5 font-medium text-slate-700 transition hover:bg-slate-200">
                                Log out
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </nav>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
