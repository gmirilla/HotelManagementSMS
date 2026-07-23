<?php

declare(strict_types=1);

namespace App\Livewire\Housekeeping;

use App\Domain\Housekeeping\Actions\AssignHousekeepingTaskAction;
use App\Domain\Housekeeping\Actions\CompleteHousekeepingTaskAction;
use App\Domain\Housekeeping\Actions\InspectHousekeepingTaskAction;
use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domain\Housekeeping\Enums\HousekeepingTaskType;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Housekeeping')]
class TaskBoard extends Component
{
    use InteractsWithActiveBranch;

    public ?int $roomId = null;

    public string $taskType = 'stayover_clean';

    /** @var array<string, bool> */
    public array $checklistDraft = [];

    public ?int $completingTaskId = null;

    #[Computed]
    public function tasksByStatus(): array
    {
        $tasks = HousekeepingTask::where('branch_id', $this->branchId)
            ->whereDate('scheduled_date', now()->toDateString())
            ->with(['room', 'assignedTo'])
            ->get();

        return collect(HousekeepingTaskStatus::cases())
            ->mapWithKeys(fn (HousekeepingTaskStatus $status) => [
                $status->value => $tasks->where('status', $status),
            ])
            ->all();
    }

    #[Computed]
    public function rooms(): Collection
    {
        return Room::where('branch_id', $this->branchId)->orderBy('room_number')->get();
    }

    #[Computed]
    public function staff(): Collection
    {
        return User::whereHas('branches', fn ($q) => $q->where('branches.id', $this->branchId))->orderBy('name')->get();
    }

    public function createTask(): void
    {
        $this->authorize('create', HousekeepingTask::class);

        $this->validate([
            'roomId' => ['required', 'integer', 'exists:rooms,id'],
            'taskType' => ['required', 'string'],
        ]);

        HousekeepingTask::create([
            'branch_id' => $this->branchId,
            'room_id' => $this->roomId,
            'task_type' => $this->taskType,
            'status' => HousekeepingTaskStatus::Pending,
            'scheduled_date' => now()->toDateString(),
            'checklist' => [
                'Bed made' => false,
                'Bathroom cleaned' => false,
                'Trash emptied' => false,
                'Amenities restocked' => false,
            ],
        ]);

        $this->reset(['roomId']);
        unset($this->tasksByStatus);
    }

    public function assignToMe(int $taskId, AssignHousekeepingTaskAction $assignTask): void
    {
        $task = HousekeepingTask::findOrFail($taskId);
        $this->authorize('update', $task);

        $assignTask->handle($task, auth()->user());
        unset($this->tasksByStatus);
    }

    public function startCompleting(int $taskId): void
    {
        $task = HousekeepingTask::findOrFail($taskId);
        $this->authorize('update', $task);

        $this->completingTaskId = $taskId;
        $this->checklistDraft = $task->checklist ?? [];
    }

    public function completeTask(CompleteHousekeepingTaskAction $completeTask): void
    {
        $task = HousekeepingTask::findOrFail($this->completingTaskId);
        $this->authorize('update', $task);

        $completeTask->handle($task, $this->checklistDraft);

        $this->completingTaskId = null;
        $this->checklistDraft = [];
        unset($this->tasksByStatus);
    }

    public function inspect(int $taskId, bool $passed, InspectHousekeepingTaskAction $inspectTask): void
    {
        $task = HousekeepingTask::findOrFail($taskId);
        $this->authorize('inspect', $task);

        $inspectTask->handle($task, auth()->user(), $passed);
        unset($this->tasksByStatus);
    }

    public function render()
    {
        return view('livewire.housekeeping.task-board', ['taskTypes' => HousekeepingTaskType::cases()]);
    }
}
