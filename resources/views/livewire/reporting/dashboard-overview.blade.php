<div>
    <div class="mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 px-6 py-8 shadow-lg shadow-brand-900/20 sm:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-white sm:text-2xl">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-brand-100">
                    {{ $this->activeBranch?->name ?? 'Your organization' }} &middot; {{ now()->format('l, F j, Y') }}
                </p>
            </div>

            @if ($this->accessibleBranches->count() > 1)
                <select wire:model.live="branchId" class="rounded-md border-0 bg-white/90 text-sm text-slate-800 shadow-sm focus:ring-2 focus:ring-white">
                    @foreach ($this->accessibleBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    @can('reports.view')
        <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <x-stat-tile label="Occupancy" :value="$this->occupancyRate . '%'" />
            <x-stat-tile label="Arrivals today" :value="$this->arrivalsToday" />
            <x-stat-tile label="Departures today" :value="$this->departuresToday" />
            <x-stat-tile label="Pending housekeeping" :value="$this->pendingHousekeepingCount" />
            <x-stat-tile label="Open maintenance" :value="$this->openMaintenanceCount" />
            <x-stat-tile label="Restaurant sales today" :value="'$' . number_format($this->restaurantSalesTodayCents / 100, 2)" />
            <x-stat-tile label="Outstanding invoices" :value="'$' . number_format($this->outstandingInvoicesCents / 100, 2)" />
        </div>

        <div class="mb-8 grid grid-cols-1 gap-4 lg:grid-cols-5">
            <div class="rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 lg:col-span-3">
                <h2 class="mb-4 font-medium text-slate-800">Revenue trend — last 14 days</h2>
                @if (array_sum($this->revenueTrendChartData['datasets'][0]['data']) > 0)
                    <x-chart
                        wire:key="revenue-trend-chart-{{ $branchId }}"
                        type="line"
                        :labels="$this->revenueTrendChartData['labels']"
                        :datasets="$this->revenueTrendChartData['datasets']"
                        :options="['plugins' => ['legend' => ['display' => false]]]"
                    />
                @else
                    <p class="py-16 text-center text-sm text-slate-500">No revenue posted in this window yet.</p>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 lg:col-span-2">
                <h2 class="mb-4 font-medium text-slate-800">Room status</h2>
                @if (array_sum($this->roomStatusChartData['datasets'][0]['data']) > 0)
                    <x-chart
                        wire:key="room-status-chart-{{ $branchId }}"
                        type="doughnut"
                        :labels="$this->roomStatusChartData['labels']"
                        :datasets="$this->roomStatusChartData['datasets']"
                        :options="['plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12, 'padding' => 12]]]]"
                    />
                @else
                    <p class="py-16 text-center text-sm text-slate-500">No rooms configured for this branch yet.</p>
                @endif
            </div>
        </div>
    @endcan

    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
        Operations
    </h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('front-desk.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Front Desk</h2>
            <p class="mt-1 text-sm text-slate-500">Today's arrivals, departures, and in-house guests.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('reservations.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Reservations</h2>
            <p class="mt-1 text-sm text-slate-500">Search, filter, and create new bookings.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('guests.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Guests</h2>
            <p class="mt-1 text-sm text-slate-500">Guest profiles, documents, and stay history.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('rooms.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Rooms</h2>
            <p class="mt-1 text-sm text-slate-500">Live room status board for this branch.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('room-types.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Room Types</h2>
            <p class="mt-1 text-sm text-slate-500">Rates, capacity, and amenities per room type.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('housekeeping.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Housekeeping</h2>
            <p class="mt-1 text-sm text-slate-500">Today's cleaning tasks and inspections.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('maintenance.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Maintenance</h2>
            <p class="mt-1 text-sm text-slate-500">Work orders, assets, and repairs.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>
    </div>

    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
        Restaurant &amp; Inventory
    </h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('restaurant.pos') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Point of Sale</h2>
            <p class="mt-1 text-sm text-slate-500">Take orders for tables and room service.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('restaurant.kitchen') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Kitchen Display</h2>
            <p class="mt-1 text-sm text-slate-500">Live queue of tickets sent to the kitchen.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('restaurant.menu') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Restaurant &amp; Menu</h2>
            <p class="mt-1 text-sm text-slate-500">Outlets, tables, categories, and menu items.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('inventory.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Inventory</h2>
            <p class="mt-1 text-sm text-slate-500">Stock levels, receipts, wastage, and adjustments.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('purchase-orders.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Purchase Orders</h2>
            <p class="mt-1 text-sm text-slate-500">Order from suppliers and receive goods.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>
    </div>

    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
        Accounting
    </h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('accounting.chart-of-accounts') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Chart of Accounts</h2>
            <p class="mt-1 text-sm text-slate-500">Assets, liabilities, equity, revenue, and expense accounts.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('accounting.journal-entries') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Journal Entries</h2>
            <p class="mt-1 text-sm text-slate-500">Post balanced double-entry journal entries.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('accounting.reports') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Financial Reports</h2>
            <p class="mt-1 text-sm text-slate-500">Trial balance, profit &amp; loss, and balance sheet.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('accounting.cashbook') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Cashbook</h2>
            <p class="mt-1 text-sm text-slate-500">Cash in/out per shift with running balances.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('accounting.ar-ap') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Receivables &amp; Payables</h2>
            <p class="mt-1 text-sm text-slate-500">Track and settle corporate and supplier balances.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('accounting.tax-rules') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Tax Rules</h2>
            <p class="mt-1 text-sm text-slate-500">Configure tax rates applied to sales.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>
    </div>

    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
        Human Resources
    </h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('hr.employees') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Employees</h2>
            <p class="mt-1 text-sm text-slate-500">Employee records, roles, and employment status.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('hr.attendance') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Attendance</h2>
            <p class="mt-1 text-sm text-slate-500">Clock in/out and daily attendance status.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('hr.leave') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Leave</h2>
            <p class="mt-1 text-sm text-slate-500">Leave requests, approvals, and balances.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('hr.payroll') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Payroll</h2>
            <p class="mt-1 text-sm text-slate-500">Process payroll runs and view payslips.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('hr.performance-reviews') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Performance Reviews</h2>
            <p class="mt-1 text-sm text-slate-500">Periodic reviews with restricted visibility.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('hr.disciplinary-records') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Disciplinary Records</h2>
            <p class="mt-1 text-sm text-slate-500">Incident records with restricted visibility.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('hr.recruitment') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Recruitment</h2>
            <p class="mt-1 text-sm text-slate-500">Job openings and candidate pipeline.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>
    </div>

    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
        CRM &amp; Events
    </h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('crm.corporate-accounts') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Corporate Accounts</h2>
            <p class="mt-1 text-sm text-slate-500">Corporate clients and travel agents with negotiated terms.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('crm.feedback') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Guest Feedback</h2>
            <p class="mt-1 text-sm text-slate-500">Log, assign, and resolve compliments and complaints.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('crm.loyalty') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Loyalty Program</h2>
            <p class="mt-1 text-sm text-slate-500">Points balances, tiers, and redemptions.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('crm.coupons') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Coupons</h2>
            <p class="mt-1 text-sm text-slate-500">Discount codes and promotions with usage limits.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('crm.campaigns') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Marketing Campaigns</h2>
            <p class="mt-1 text-sm text-slate-500">Segment-targeted email/SMS campaigns.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('events.spaces') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Event Spaces</h2>
            <p class="mt-1 text-sm text-slate-500">Conference halls, meeting rooms, and catering services.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('events.bookings') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Event Bookings</h2>
            <p class="mt-1 text-sm text-slate-500">Bookings with a consolidated venue + services bill.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>
    </div>

    @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('settings.manage') || auth()->user()->can('branches.manage'))
        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
            Administration
        </h2>
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @can('viewAny', App\Models\User::class)
                <a href="{{ route('admin.users') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
                    <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Users</h2>
                    <p class="mt-1 text-sm text-slate-500">Create staff accounts and manage roles and branch access.</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
                </a>
            @endcan
            @if (auth()->user()->can('branches.manage'))
                <a href="{{ route('admin.branches') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
                    <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Branches</h2>
                    <p class="mt-1 text-sm text-slate-500">Add and manage the properties in your organization.</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
                </a>
            @endif
            @if (auth()->user()->can('settings.manage'))
                <a href="{{ route('admin.hotel-settings') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
                    <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Hotel Settings</h2>
                    <p class="mt-1 text-sm text-slate-500">Hotel name, logo, default currency, and timezone.</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
                </a>
                <a href="{{ route('admin.appearance') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
                    <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Appearance</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose a brand color for your organization.</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
                </a>
            @endif
        </div>
    @endif

    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
        Account
    </h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('mfa.setup') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Two-factor authentication</h2>
            <p class="mt-1 text-sm text-slate-500">Add an extra layer of security to your account.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('password.edit') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Password</h2>
            <p class="mt-1 text-sm text-slate-500">Change your account password.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>

        <a href="{{ route('sessions.index') }}" class="group relative flex flex-col rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
            <h2 class="font-medium text-slate-800 group-hover:text-brand-700">Active sessions</h2>
            <p class="mt-1 text-sm text-slate-500">See and revoke devices signed in to your account.</p>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Open <span aria-hidden="true">&rarr;</span></span>
        </a>
    </div>
</div>
