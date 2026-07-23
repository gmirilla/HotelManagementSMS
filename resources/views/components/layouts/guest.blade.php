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
    <div class="flex min-h-full flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center gap-2">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold">H</div>
            <span class="text-lg font-semibold text-slate-800">{{ config('app.name') }}</span>
        </div>

        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
