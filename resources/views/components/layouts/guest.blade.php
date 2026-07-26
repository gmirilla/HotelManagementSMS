@props(['title' => null, 'split' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title — " : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- No tenant is known before login, so the guest layout always uses the default brand ramp. --}}
</head>
<body class="h-full font-sans antialiased text-slate-900">
    @if ($split)
        <div class="relative flex min-h-full items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-brand-50 via-white to-slate-50"></div>
            <div class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-[36rem] w-[48rem] -translate-x-1/2 rounded-full bg-brand-200/30 blur-3xl"></div>

            <div class="grid w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-2xl shadow-slate-900/10 lg:grid-cols-2">
                {{-- Brand panel — hidden below lg so the form is never pushed down the page on mobile. --}}
                <div class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-slate-900 p-10 text-white lg:flex">
                    <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-32 -left-16 h-72 w-72 rounded-full bg-brand-400/20 blur-3xl"></div>

                    <div class="relative">
                        <div class="mb-10 flex items-center gap-2.5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 font-bold backdrop-blur">H</div>
                            <span class="text-lg font-semibold">{{ config('app.name') }}</span>
                        </div>

                        <h2 class="text-2xl font-semibold leading-snug text-balance">Run every part of your property from one place.</h2>
                        <p class="mt-3 text-sm leading-relaxed text-brand-100/90">Front desk, housekeeping, restaurant, inventory, and accounting — all in sync, in real time.</p>
                    </div>

                    <ul class="relative space-y-4 text-sm">
                        <li class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                </svg>
                            </span>
                            Front desk &amp; reservations
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                            </span>
                            Restaurant &amp; inventory
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                            </span>
                            Multi-branch accounting
                        </li>
                    </ul>
                </div>

                {{-- Form panel --}}
                <div class="flex flex-col justify-center p-8 sm:p-10">
                    <div class="mb-8 flex items-center gap-2 lg:hidden">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-white shadow-lg shadow-brand-600/25">H</div>
                        <span class="text-base font-semibold text-slate-800">{{ config('app.name') }}</span>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    @else
        <div class="relative flex min-h-full flex-col items-center justify-center overflow-hidden px-4 py-12 sm:px-6 lg:px-8">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-brand-50 via-white to-slate-50"></div>
            <div class="pointer-events-none absolute -top-32 left-1/2 -z-10 h-96 w-[36rem] -translate-x-1/2 rounded-full bg-brand-200/40 blur-3xl"></div>

            <div class="mb-8 flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-bold text-white shadow-lg shadow-brand-600/25">H</div>
                <span class="text-lg font-semibold text-slate-800">{{ config('app.name') }}</span>
            </div>

            <div class="w-full max-w-md rounded-2xl border border-slate-200/70 bg-white/90 p-8 shadow-xl shadow-slate-900/5 backdrop-blur">
                {{ $slot }}
            </div>
        </div>
    @endif
</body>
</html>
