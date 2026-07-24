<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Users</h1>
            <p class="text-sm text-slate-500">Staff accounts, roles, and branch assignments for your organization.</p>
        </div>

        <div class="flex items-center gap-3">
            <x-text-input type="search" wire:model.live.debounce.300ms="search" placeholder="Search users…" class="mt-0 w-56" />

            @can('create', App\Models\User::class)
                <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    New user
                </button>
            @endcan
        </div>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6 sm:grid-cols-2">
            <div>
                <x-input-label value="Name" />
                <x-text-input type="text" wire:model="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label value="Email" />
                <x-text-input type="email" wire:model="email" />
                <x-input-error :messages="$errors->get('email')" />
            </div>
            <div>
                <x-input-label :value="$editingUserId ? 'New password (leave blank to keep current)' : 'Password'" />
                <x-text-input type="password" wire:model="password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>
            <div>
                <x-input-label value="Confirm password" />
                <x-text-input type="password" wire:model="password_confirmation" />
            </div>
            <div>
                <x-input-label value="Role" />
                <select wire:model="roleName" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">Select…</option>
                    @foreach ($this->roleOptions as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('roleName')" />
            </div>
            <div>
                <x-input-label value="Primary branch" />
                <select wire:model="primaryBranchId" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">None (tenant/group-wide role)</option>
                    @foreach ($this->branchOptions as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-full">
                <x-input-label value="Branch assignments" />
                <p class="mt-1 text-xs text-slate-500">Not required for tenant/group-wide roles (Super Administrator, Hotel Owner, General Manager, Auditor).</p>
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($this->branchOptions as $branch)
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="selectedBranchIds" value="{{ $branch->id }}" class="rounded border-slate-300">
                            {{ $branch->name }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('selectedBranchIds')" />
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Branches</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-2">
                            <p class="font-medium text-slate-800">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $user->branches->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($user->trashed())
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-600">Deactivated</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Active</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-xs">
                            @can('update', $user)
                                @unless ($user->trashed())
                                    <button wire:click="edit({{ $user->id }})" class="font-medium text-brand-600 hover:text-brand-500">Edit</button>
                                @endunless
                            @endcan
                            @can('delete', $user)
                                @if (! $user->trashed())
                                    <button wire:click="deactivate({{ $user->id }})" wire:confirm="Deactivate this user? They will no longer be able to sign in." class="ml-3 font-medium text-red-600 hover:text-red-500">Deactivate</button>
                                @endif
                            @endcan
                            @can('restore', $user)
                                @if ($user->trashed())
                                    <button wire:click="reactivate({{ $user->id }})" class="font-medium text-emerald-600 hover:text-emerald-500">Reactivate</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No users yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
