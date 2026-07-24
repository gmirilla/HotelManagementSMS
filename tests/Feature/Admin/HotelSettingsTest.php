<?php

declare(strict_types=1);

use App\Domain\Settings\Actions\UpdateHotelSettingsAction;
use App\Livewire\Admin\HotelSettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('updating hotel settings changes name, currency, and timezone', function (): void {
    $tenant = Tenant::factory()->create(['name' => 'Old Name', 'default_currency' => 'USD', 'default_timezone' => 'UTC']);

    app(UpdateHotelSettingsAction::class)->handle($tenant, 'New Name', 'EUR', 'Europe/London');

    expect($tenant->fresh()->name)->toBe('New Name')
        ->and($tenant->fresh()->default_currency)->toBe('EUR')
        ->and($tenant->fresh()->default_timezone)->toBe('Europe/London');
});

test('uploading a new logo stores it and deletes the previous file', function (): void {
    Storage::fake('public');
    $tenant = Tenant::factory()->create();

    $first = UploadedFile::fake()->image('logo1.png');
    app(UpdateHotelSettingsAction::class)->handle($tenant, $tenant->name, $tenant->default_currency, $tenant->default_timezone, $first);
    $firstPath = $tenant->fresh()->logo_path;
    Storage::disk('public')->assertExists($firstPath);

    $second = UploadedFile::fake()->image('logo2.png');
    app(UpdateHotelSettingsAction::class)->handle($tenant->fresh(), $tenant->name, $tenant->default_currency, $tenant->default_timezone, $second);

    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($tenant->fresh()->logo_path);
});

test('a settings.manage holder can update hotel settings through the component', function (): void {
    Storage::fake('public');
    Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Hotel Settings Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('settings.manage');

    $tenant = Tenant::factory()->create(['name' => 'Aurora Hotels']);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(HotelSettings::class)
        ->set('name', 'Aurora Grand Hotels')
        ->set('defaultCurrency', 'gbp')
        ->set('defaultTimezone', 'Europe/London')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('save')
        ->assertHasNoErrors();

    $tenant->refresh();
    expect($tenant->name)->toBe('Aurora Grand Hotels')
        ->and($tenant->default_currency)->toBe('GBP')
        ->and($tenant->default_timezone)->toBe('Europe/London')
        ->and($tenant->logo_path)->not->toBeNull();
});

test('a user without settings.manage cannot open hotel settings', function (): void {
    Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Livewire::actingAs($user)->test(HotelSettings::class)->assertForbidden();
});

test('an invalid timezone is rejected', function (): void {
    Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Hotel Settings Manager 2', 'guard_name' => 'web']);
    $role->givePermissionTo('settings.manage');

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(HotelSettings::class)
        ->set('defaultTimezone', 'Not/ARealZone')
        ->call('save')
        ->assertHasErrors(['defaultTimezone']);
});
