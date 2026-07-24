<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Housekeeping</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }} &middot; {{ now()->format('l, F j') }}</p>
            @endif
        </div>

        @can('create', App\Models\HousekeepingTask::class)
            <form wire:submit="createTask" class="flex items-center gap-2">
                <select wire:model="roomId" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="">Room&hellip;</option>
                    @foreach ($this->rooms as $room)
                        <option value="{{ $room->id }}">{{ $room->room_number }}</option>
                    @endforeach
                </select>
                <select wire:model="taskType" class="rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($taskTypes as $type)
                        <option value="{{ $type->value }}">{{ ucfirst(str_replace('_', ' ', $type->value)) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-500">
                    Add task
                </button>
            </form>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
        @foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'awaiting_inspection' => 'Awaiting Inspection', 'completed' => 'Completed', 'failed_inspection' => 'Failed Inspection'] as $status => $label)
            <div class="rounded-lg bg-slate-50 p-3">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }} ({{ $this->tasksByStatus[$status]->count() }})</h2>

                <div class="space-y-2">
                    @foreach ($this->tasksByStatus[$status] as $task)
                        <div wire:key="task-{{ $task->id }}" class="rounded-md border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-3 text-sm">
                            <p class="font-medium text-slate-800">Room {{ $task->room->room_number }}</p>
                            <p class="text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $task->task_type->value)) }}</p>
                            <p class="text-xs text-slate-500">{{ $task->assignedTo?->name ?? 'Unassigned' }}</p>

                            @if ($status === 'pending' && ! $task->assigned_to_user_id)
                                <button wire:click="assignToMe({{ $task->id }})" class="mt-2 text-xs font-medium text-brand-600 hover:text-brand-500">
                                    Assign to me
                                </button>
                            @endif

                            @if (in_array($status, ['pending', 'in_progress']) && $task->assigned_to_user_id)
                                @if ($completingTaskId === $task->id)
                                    <div class="mt-2 space-y-1">
                                        @foreach ($checklistDraft as $item => $done)
                                            <label class="flex items-center gap-2 text-xs">
                                                <input type="checkbox" wire:model="checklistDraft.{{ $item }}" class="rounded border-slate-300 text-brand-600">
                                                {{ $item }}
                                            </label>
                                        @endforeach
                                        <button wire:click="completeTask" class="mt-1 text-xs font-medium text-emerald-600 hover:text-emerald-500">
                                            Submit for inspection
                                        </button>
                                    </div>
                                @else
                                    <button wire:click="startCompleting({{ $task->id }})" class="mt-2 text-xs font-medium text-brand-600 hover:text-brand-500">
                                        Complete checklist
                                    </button>
                                @endif
                            @endif

                            @if ($status === 'awaiting_inspection')
                                @can('inspect', $task)
                                    <div class="mt-2 flex gap-2">
                                        <button wire:click="inspect({{ $task->id }}, true)" class="text-xs font-medium text-emerald-600 hover:text-emerald-500">Pass</button>
                                        <button wire:click="inspect({{ $task->id }}, false)" class="text-xs font-medium text-red-600 hover:text-red-500">Fail</button>
                                    </div>
                                @endcan
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
