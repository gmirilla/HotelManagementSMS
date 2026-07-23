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
                            <a href="{{ route('rooms.index') }}" class="hover:text-indigo-600">Rooms</a>
                            <a href="{{ route('room-types.index') }}" class="hover:text-indigo-600">Room Types</a>
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
