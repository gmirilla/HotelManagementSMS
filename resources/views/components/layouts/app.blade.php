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
    <div class="flex h-full flex-col" x-data="{ sidebarOpen: false }">
        <nav class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/85 backdrop-blur-md">
            <div class="flex items-center justify-between px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    @auth
                        <button @click="sidebarOpen = true" class="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                    @endauth

                    @php $tenant = auth()->user()?->tenant; @endphp
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        @if ($tenant?->logo_path)
                            <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="" class="h-8 w-8 rounded-lg object-cover">
                        @else
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-sm shadow-brand-600/30">
                                {{ strtoupper(substr($tenant?->name ?? config('app.name'), 0, 1)) }}
                            </div>
                        @endif
                        <span class="font-semibold text-slate-800">{{ $tenant?->name ?? config('app.name') }}</span>
                    </a>

                    @auth
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
                        <a href="{{ route('mfa.setup') }}" class="hidden text-slate-600 hover:text-brand-600 sm:inline">Security</a>
                        <a href="{{ route('sessions.index') }}" class="hidden text-slate-600 hover:text-brand-600 sm:inline">Sessions</a>
                        <span class="hidden text-slate-300 sm:inline">|</span>
                        <span class="hidden font-medium text-slate-700 sm:inline">{{ auth()->user()->name }}</span>
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

        <div class="flex min-h-0 flex-1">
            @auth
                <div
                    x-show="sidebarOpen"
                    x-cloak
                    x-transition.opacity
                    @click="sidebarOpen = false"
                    class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
                ></div>

                <aside
                    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                    class="fixed inset-y-0 left-0 z-40 w-64 min-h-0 transform border-r border-slate-200/70 bg-white transition-transform duration-200 ease-in-out lg:static lg:z-auto lg:w-64 lg:translate-x-0 lg:transition-none"
                >
                    <div class="flex items-center justify-between border-b border-slate-200/70 px-4 py-3 lg:hidden">
                        <span class="text-sm font-semibold text-slate-700">Menu</span>
                        <button @click="sidebarOpen = false" class="rounded-md p-1 text-slate-500 transition hover:bg-slate-100" aria-label="Close menu">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    @include('components.layouts.partials.sidebar')
                </aside>
            @endauth

            <div class="min-h-0 flex-1 overflow-y-auto">
                <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</body>
</html>
