<?php

declare(strict_types=1);

namespace App\Livewire\Maintenance;

use App\Domain\Maintenance\Actions\CompleteMaintenanceWorkOrderAction;
use App\Domain\Maintenance\Actions\CreateMaintenanceWorkOrderAction;
use App\Domain\Maintenance\Actions\VerifyMaintenanceWorkOrderAction;
use App\Domain\Maintenance\Enums\WorkOrderPriority;
use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\MaintenanceWorkOrder;
use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Maintenance')]
class WorkOrderManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public ?int $roomId = null;

    public string $description = '';

    public string $priority = 'medium';

    public bool $takeRoomOutOfOrder = false;

    public string $statusFilter = '';

    public ?int $completingId = null;

    public string $partsCost = '0';

    public string $laborCost = '0';

    protected function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'string'],
            'roomId' => ['nullable', 'integer', 'exists:rooms,id'],
        ];
    }

    #[Computed]
    public function workOrders(): Collection
    {
        return MaintenanceWorkOrder::where('branch_id', $this->branchId)
            ->with(['room', 'assignedTo', 'reportedBy'])
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function rooms(): Collection
    {
        return Room::where('branch_id', $this->branchId)->orderBy('room_number')->get();
    }

    public function create(CreateMaintenanceWorkOrderAction $createWorkOrder): void
    {
        $this->authorize('create', MaintenanceWorkOrder::class);
        $this->validate();

        $room = $this->roomId ? Room::find($this->roomId) : null;

        $createWorkOrder->handle(
            branchId: $this->branchId,
            reportedBy: auth()->user(),
            description: $this->description,
            priority: WorkOrderPriority::from($this->priority),
            room: $room,
            takeRoomOutOfOrder: $this->takeRoomOutOfOrder,
        );

        $this->reset(['description', 'roomId', 'takeRoomOutOfOrder', 'showForm']);
        $this->priority = 'medium';
        unset($this->workOrders);
    }

    public function startCompleting(int $workOrderId): void
    {
        $this->completingId = $workOrderId;
        $this->partsCost = '0';
        $this->laborCost = '0';
    }

    public function complete(CompleteMaintenanceWorkOrderAction $completeWorkOrder): void
    {
        $workOrder = MaintenanceWorkOrder::findOrFail($this->completingId);
        $this->authorize('update', $workOrder);

        $completeWorkOrder->handle(
            $workOrder,
            (int) round(((float) $this->partsCost) * 100),
            (int) round(((float) $this->laborCost) * 100),
        );

        $this->completingId = null;
        unset($this->workOrders);
    }

    public function verify(int $workOrderId, VerifyMaintenanceWorkOrderAction $verifyWorkOrder): void
    {
        $workOrder = MaintenanceWorkOrder::findOrFail($workOrderId);
        $this->authorize('verify', $workOrder);

        $verifyWorkOrder->handle($workOrder, auth()->user());
        unset($this->workOrders);
    }

    public function render()
    {
        return view('livewire.maintenance.work-order-manager', [
            'priorities' => WorkOrderPriority::cases(),
            'statuses' => WorkOrderStatus::cases(),
        ]);
    }
}
