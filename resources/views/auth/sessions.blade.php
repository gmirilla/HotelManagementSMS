<x-layouts.app title="Active sessions">
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-slate-900">Active sessions</h1>

            <form method="POST" action="{{ route('sessions.destroy-others') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100">
                    Log out other sessions
                </button>
            </form>
        </div>

        <div class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
            @foreach ($sessions as $session)
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="text-sm font-medium text-slate-800">
                            {{ $session['ip_address'] }}
                            @if ($session['is_current_device'])
                                <span class="ml-2 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">This device</span>
                            @endif
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ str($session['user_agent'])->limit(60) }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Last active {{ $session['last_active']->diffForHumans() }}</p>
                    </div>

                    @unless ($session['is_current_device'])
                        <form method="POST" action="{{ route('sessions.destroy', $session['id']) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Revoke</button>
                        </form>
                    @endunless
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
