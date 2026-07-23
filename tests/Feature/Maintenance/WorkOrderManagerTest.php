<?php

declare(strict_types=1);

use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Livewire\Maintenance\WorkOrderManager;
use App\Models\Branch;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'maintenance.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Maintenance Officer', 'guard_name' => 'web']);
    $role->givePermissionTo('maintenance.manage');

    $this->branch = Branch::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('creating, completing, and verifying a work order works end to end through the component', function (): void {
    $component = Livewire::actingAs($this->staff)
        ->test(WorkOrderManager::class)
        ->set('description', 'Broken thermostat')
        ->set('priority', 'high')
        ->call('create')
        ->assertHasNoErrors();

    $workOrder = MaintenanceWorkOrder::where('description', 'Broken thermostat')->firstOrFail();

    $component->call('startCompleting', $workOrder->id)
        ->set('partsCost', '25.00')
        ->set('laborCost', '50.00')
        ->call('complete');

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Completed)
        ->and($workOrder->fresh()->totalCostCents())->toBe(7500);

    $component->call('verify', $workOrder->id);

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Verified);
});

test('the status filter narrows the visible work orders', function (): void {
    MaintenanceWorkOrder::factory()->create(['branch_id' => $this->branch->id, 'status' => WorkOrderStatus::Open, 'description' => 'Open ticket']);
    MaintenanceWorkOrder::factory()->create(['branch_id' => $this->branch->id, 'status' => WorkOrderStatus::Verified, 'description' => 'Closed ticket']);

    Livewire::actingAs($this->staff)
        ->test(WorkOrderManager::class)
        ->set('statusFilter', 'open')
        ->assertSee('Open ticket')
        ->assertDontSee('Closed ticket');
});
