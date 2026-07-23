<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('a user can view their active sessions', function (): void {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'other-session-id',
        'user_id' => $user->id,
        'ip_address' => '10.0.0.5',
        'user_agent' => 'Mozilla/5.0 Test Agent',
        'payload' => base64_encode('test'),
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->actingAs($user)->get(route('sessions.index'));

    $response->assertOk();
    $response->assertSee('10.0.0.5');
});

test('a user can revoke a specific session, but not another user\'s session', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    DB::table('sessions')->insert([
        ['id' => 'mine', 'user_id' => $user->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'A', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'not-mine', 'user_id' => $otherUser->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'B', 'payload' => 'x', 'last_activity' => now()->timestamp],
    ]);

    $this->actingAs($user)->delete(route('sessions.destroy', 'mine'));
    $this->actingAs($user)->delete(route('sessions.destroy', 'not-mine'));

    expect(DB::table('sessions')->where('id', 'mine')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'not-mine')->exists())->toBeTrue();
});

test('a user can revoke all other sessions at once', function (): void {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        ['id' => 'session-a', 'user_id' => $user->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'A', 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'session-b', 'user_id' => $user->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'B', 'payload' => 'x', 'last_activity' => now()->timestamp],
    ]);

    $this->actingAs($user)->withSession(['_token' => 'test'])
        ->delete(route('sessions.destroy-others'));

    // Both seeded rows are "other" sessions relative to the test client's own
    // (different) session id, so both should be gone.
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});
