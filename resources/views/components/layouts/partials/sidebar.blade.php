{{--
    Primary module navigation. Split out of app.blade.php so the layout
    shell (top bar + sidebar + content grid) stays readable — this partial
    owns every nav link and its @can/@if guard, carried over 1:1 from the
    old top-bar dropdowns.
--}}
<nav class="flex h-full flex-col gap-1 overflow-y-auto px-3 py-4 text-sm">
    <a href="{{ route('dashboard') }}" @class([
        'rounded-md px-3 py-2 font-medium transition',
        'bg-brand-50 text-brand-700' => request()->routeIs('dashboard'),
        'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('dashboard'),
    ])>Dashboard</a>

    <a href="{{ route('front-desk.index') }}" @class([
        'rounded-md px-3 py-2 font-medium transition',
        'bg-brand-50 text-brand-700' => request()->routeIs('front-desk.*', 'folios.*'),
        'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('front-desk.*', 'folios.*'),
    ])>Front Desk</a>

    <a href="{{ route('reservations.index') }}" @class([
        'rounded-md px-3 py-2 font-medium transition',
        'bg-brand-50 text-brand-700' => request()->routeIs('reservations.*'),
        'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('reservations.*'),
    ])>Reservations</a>

    <a href="{{ route('guests.index') }}" @class([
        'rounded-md px-3 py-2 font-medium transition',
        'bg-brand-50 text-brand-700' => request()->routeIs('guests.*'),
        'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('guests.*'),
    ])>Guests</a>

    <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Operations</p>
    <a href="{{ route('rooms.index') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('rooms.*'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('rooms.*')])>Rooms</a>
    <a href="{{ route('room-types.index') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('room-types.*'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('room-types.*')])>Room Types</a>
    <a href="{{ route('housekeeping.index') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('housekeeping.*'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('housekeeping.*')])>Housekeeping</a>
    <a href="{{ route('maintenance.index') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('maintenance.*'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('maintenance.*')])>Maintenance</a>

    <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Restaurant &amp; Inventory</p>
    <a href="{{ route('restaurant.pos') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('restaurant.pos'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('restaurant.pos')])>POS</a>
    <a href="{{ route('restaurant.kitchen') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('restaurant.kitchen'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('restaurant.kitchen')])>Kitchen Display</a>
    <a href="{{ route('restaurant.menu') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('restaurant.menu'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('restaurant.menu')])>Menu</a>
    <a href="{{ route('inventory.index') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('inventory.*'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('inventory.*')])>Inventory</a>
    <a href="{{ route('purchase-orders.index') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('purchase-orders.*'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('purchase-orders.*')])>Purchase Orders</a>

    <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Accounting</p>
    <a href="{{ route('accounting.chart-of-accounts') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('accounting.chart-of-accounts'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('accounting.chart-of-accounts')])>Chart of Accounts</a>
    <a href="{{ route('accounting.journal-entries') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('accounting.journal-entries'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('accounting.journal-entries')])>Journal Entries</a>
    <a href="{{ route('accounting.reports') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('accounting.reports'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('accounting.reports')])>Financial Reports</a>
    <a href="{{ route('accounting.cashbook') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('accounting.cashbook'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('accounting.cashbook')])>Cashbook</a>
    <a href="{{ route('accounting.ar-ap') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('accounting.ar-ap'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('accounting.ar-ap')])>Receivables &amp; Payables</a>
    <a href="{{ route('accounting.tax-rules') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('accounting.tax-rules'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('accounting.tax-rules')])>Tax Rules</a>

    <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">HR</p>
    <a href="{{ route('hr.employees') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('hr.employees'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('hr.employees')])>Employees</a>
    <a href="{{ route('hr.attendance') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('hr.attendance'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('hr.attendance')])>Attendance</a>
    <a href="{{ route('hr.leave') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('hr.leave'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('hr.leave')])>Leave</a>
    <a href="{{ route('hr.payroll') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('hr.payroll'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('hr.payroll')])>Payroll</a>
    <a href="{{ route('hr.performance-reviews') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('hr.performance-reviews'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('hr.performance-reviews')])>Performance Reviews</a>
    <a href="{{ route('hr.disciplinary-records') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('hr.disciplinary-records'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('hr.disciplinary-records')])>Disciplinary Records</a>
    <a href="{{ route('hr.recruitment') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('hr.recruitment'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('hr.recruitment')])>Recruitment</a>

    <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">CRM &amp; Events</p>
    <a href="{{ route('crm.corporate-accounts') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('crm.corporate-accounts'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('crm.corporate-accounts')])>Corporate Accounts</a>
    <a href="{{ route('crm.feedback') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('crm.feedback'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('crm.feedback')])>Guest Feedback</a>
    <a href="{{ route('crm.loyalty') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('crm.loyalty'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('crm.loyalty')])>Loyalty Program</a>
    <a href="{{ route('crm.coupons') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('crm.coupons'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('crm.coupons')])>Coupons</a>
    <a href="{{ route('crm.campaigns') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('crm.campaigns'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('crm.campaigns')])>Marketing Campaigns</a>
    <div class="my-1 border-t border-slate-100"></div>
    <a href="{{ route('events.spaces') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('events.spaces'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('events.spaces')])>Event Spaces</a>
    <a href="{{ route('events.bookings') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('events.bookings'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('events.bookings')])>Event Bookings</a>

    @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('settings.manage') || auth()->user()->can('branches.manage'))
        <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Administration</p>
        @can('viewAny', App\Models\User::class)
            <a href="{{ route('admin.users') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('admin.users'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('admin.users')])>Users</a>
        @endcan
        @if (auth()->user()->can('branches.manage'))
            <a href="{{ route('admin.branches') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('admin.branches'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('admin.branches')])>Branches</a>
        @endif
        @if (auth()->user()->can('settings.manage'))
            <a href="{{ route('admin.hotel-settings') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('admin.hotel-settings'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('admin.hotel-settings')])>Hotel Settings</a>
            <a href="{{ route('admin.appearance') }}" @class(['rounded-md px-3 py-2 font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs('admin.appearance'), 'text-slate-600 hover:bg-brand-50 hover:text-brand-600' => ! request()->routeIs('admin.appearance')])>Appearance</a>
        @endif
    @endif
</nav>
