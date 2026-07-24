@props(['title' => null])
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
</body>
</html>
