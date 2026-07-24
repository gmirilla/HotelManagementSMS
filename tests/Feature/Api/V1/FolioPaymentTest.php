<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Enums\ChargeType;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'folios.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'payments.process', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Api Cashier', 'guard_name' => 'web']);
    $role->givePermissionTo(['folios.manage', 'payments.process']);

    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    $this->user->assignRole($role);
    $this->branch->staff()->attach($this->user->id, ['role_id' => $role->id, 'is_primary' => true]);
    $this->token = $this->user->createToken('test', ['invoices:read', 'payments:write'])->plainTextToken;

    $this->folio = Folio::factory()->create(['branch_id' => $this->branch->id]);
    $this->folio->charges()->create(['charge_type' => ChargeType::Room, 'description' => 'Room charge', 'amount_cents' => 20000, 'charge_date' => now()->toDateString()]);
});

test('showing a folio returns its charges', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")->getJson("/api/v1/folios/{$this->folio->id}");

    $response->assertOk()->assertJsonCount(1, 'data.charges');
});

test('recording a payment requires the payments:write ability', function (): void {
    $tokenWithoutAbility = $this->user->createToken('read-only', ['invoices:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$tokenWithoutAbility}")
        ->postJson("/api/v1/folios/{$this->folio->id}/payments", ['method' => 'cash', 'amount' => 100])
        ->assertStatus(403);
});

test('recording a cash payment reduces the folio balance', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/folios/{$this->folio->id}/payments", ['method' => 'cash', 'amount' => 200]);

    $response->assertCreated()->assertJsonPath('data.amount_cents', 20000);

    expect($this->folio->fresh()->balance_cents)->toBe(0);
});

test('a gateway payment method is rejected — those are webhook-driven, not recorded synchronously', function (): void {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/folios/{$this->folio->id}/payments", ['method' => 'stripe', 'amount' => 200])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error');
});

test('a payment on a folio outside the user\'s branch is forbidden', function (): void {
    $otherBranch = Branch::factory()->create();
    $otherFolio = Folio::factory()->create(['branch_id' => $otherBranch->id]);

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/folios/{$otherFolio->id}/payments", ['method' => 'cash', 'amount' => 50])
        ->assertStatus(403);
});
