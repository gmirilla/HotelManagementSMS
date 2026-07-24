<?php

declare(strict_types=1);

/**
 * NFR-PERF-003: list endpoints must not N+1. Rather than asserting an
 * arbitrary query-count ceiling (which drifts and stops meaning anything),
 * each test renders the same view against a small dataset and a larger one
 * and asserts the query count stayed flat — that's what actually proves
 * eager loading is doing its job regardless of how many rows are rendered.
 *
 * Each pair of measurements is preceded by one unmeasured "warm-up" render.
 * Without it, the *first* measured call picks up Spatie's permission/role
 * cache being populated (several extra queries) while the second call gets
 * to reuse that already-warm cache — a purely test-methodology artifact
 * that has nothing to do with the eager loading actually being asserted,
 * and which produces a false N+1 failure if left in.
 */

use App\Domain\CRM\Actions\LogGuestFeedbackAction;
use App\Domain\CRM\Enums\FeedbackType;
use App\Livewire\CRM\FeedbackManager;
use App\Livewire\Reservations\ReservationManager;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('the reservations list does not N+1 as the number of reservations grows', function (): void {
    Permission::firstOrCreate(['name' => 'reservations.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'QueryCount Reservation Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.view');

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['tenant_id' => $branch->tenant_id, 'current_branch_id' => $branch->id]);
    $user->assignRole($role);
    $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    Reservation::factory()->count(2)->create(['branch_id' => $branch->id]);

    Livewire::actingAs($user)->test(ReservationManager::class); // warm-up
    $queriesForTwo = countQueries(fn () => Livewire::actingAs($user)->test(ReservationManager::class)->assertOk());

    Reservation::factory()->count(12)->create(['branch_id' => $branch->id]);
    $queriesForFourteen = countQueries(fn () => Livewire::actingAs($user)->test(ReservationManager::class)->assertOk());

    expect($queriesForFourteen)->toBe($queriesForTwo);
});

test('the guest feedback list does not N+1 as the number of feedback items grows', function (): void {
    Permission::firstOrCreate(['name' => 'crm.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'QueryCount Feedback Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('crm.manage');

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['tenant_id' => $branch->tenant_id, 'current_branch_id' => $branch->id]);
    $user->assignRole($role);
    $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    $logFeedback = app(LogGuestFeedbackAction::class);
    $guest = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);

    $logFeedback->handle($branch, $guest, FeedbackType::Complaint, 'Subject 1', 'Description 1');

    Livewire::actingAs($user)->test(FeedbackManager::class); // warm-up
    $queriesForOne = countQueries(fn () => Livewire::actingAs($user)->test(FeedbackManager::class)->assertOk());

    foreach (range(1, 10) as $i) {
        $logFeedback->handle($branch, $guest, FeedbackType::Complaint, "Subject {$i}", "Description {$i}");
    }
    $queriesForEleven = countQueries(fn () => Livewire::actingAs($user)->test(FeedbackManager::class)->assertOk());

    expect($queriesForEleven)->toBe($queriesForOne);
});

test('the reservations API index does not N+1 as the number of bookings grows', function (): void {
    Permission::firstOrCreate(['name' => 'reservations.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'QueryCount Api Reservation Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.view');

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['tenant_id' => $branch->tenant_id]);
    $user->assignRole($role);
    $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);
    $token = $user->createToken('test', ['bookings:read'])->plainTextToken;

    Reservation::factory()->count(2)->create(['branch_id' => $branch->id]);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson("/api/v1/reservations?branch_id={$branch->id}"); // warm-up
    $queriesForTwo = countQueries(fn () => $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/reservations?branch_id={$branch->id}")
        ->assertOk());

    Reservation::factory()->count(12)->create(['branch_id' => $branch->id]);
    $queriesForFourteen = countQueries(fn () => $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/reservations?branch_id={$branch->id}")
        ->assertOk());

    expect($queriesForFourteen)->toBe($queriesForTwo);
});
