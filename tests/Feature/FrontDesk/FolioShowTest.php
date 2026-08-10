<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Livewire\FrontDesk\FolioShow;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'folios.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'payments.process', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Folio Receptionist', 'guard_name' => 'web']);
    $role->givePermissionTo('folios.manage');

    $this->branch = Branch::factory()->create();
    $this->guest = Guest::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    $this->folio = Folio::factory()->create([
        'branch_id' => $this->branch->id,
        'guest_id' => $this->guest->id,
        'status' => FolioStatus::Closed,
        'closed_at' => now(),
    ]);
    $this->folio->charges()->create([
        'charge_type' => ChargeType::Room,
        'description' => 'Room charge',
        'amount_cents' => 10000,
        'charge_date' => now()->toDateString(),
    ]);

    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('an authorized user can download the closed folio as a PDF receipt', function (): void {
    Livewire::actingAs($this->staff)
        ->test(FolioShow::class, ['folio' => $this->folio])
        ->call('downloadReceipt')
        ->assertFileDownloaded("receipt-{$this->folio->id}.pdf");
});

test('a user without folios.view/folios.manage cannot download the receipt', function (): void {
    $otherStaff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);

    Livewire::actingAs($otherStaff)->test(FolioShow::class, ['folio' => $this->folio])->assertForbidden();
});
