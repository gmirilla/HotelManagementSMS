<?php

declare(strict_types=1);

use App\Domain\Settings\Actions\UpdateTenantBrandColorAction;
use App\Livewire\Admin\AppearanceSettings;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Theme\BrandPalette;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);
    $this->ownerRole = Role::firstOrCreate(['name' => 'Hotel Owner', 'guard_name' => 'web']);
    $this->ownerRole->givePermissionTo('settings.manage');
});

test('the appearance action persists a valid hex color to the tenant', function (): void {
    $tenant = Tenant::factory()->create(['brand_color' => null]);

    app(UpdateTenantBrandColorAction::class)->handle($tenant, '#059669');

    expect($tenant->fresh()->brand_color)->toBe('#059669');
});

test('the appearance action rejects an invalid hex color', function (): void {
    $tenant = Tenant::factory()->create();

    app(UpdateTenantBrandColorAction::class)->handle($tenant, 'not-a-color');
})->throws(ValidationException::class);

test('a user without settings.manage is forbidden from the appearance screen', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(AppearanceSettings::class)->assertForbidden();
});

test('a settings.manage holder can render the appearance screen and pick a preset', function (): void {
    $user = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
    $user->assignRole($this->ownerRole);

    $component = Livewire::actingAs($user)
        ->test(AppearanceSettings::class)
        ->assertOk()
        ->assertSet('selectedColor', BrandPalette::DEFAULT_COLOR);

    $component->call('selectPreset', '#e11d48')
        ->assertSet('selectedColor', '#e11d48')
        ->assertOk();

    expect($component->get('ramp')[600])->toBe('#e11d48');
});

test('a settings.manage holder can save a new brand color and it persists to their tenant', function (): void {
    $user = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
    $user->assignRole($this->ownerRole);

    Livewire::actingAs($user)
        ->test(AppearanceSettings::class)
        ->set('selectedColor', '#0d9488')
        ->call('save');

    expect($user->tenant->fresh()->brand_color)->toBe('#0d9488');
});

test('saving an invalid hex color fails validation and does not persist', function (): void {
    $user = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
    $user->assignRole($this->ownerRole);

    Livewire::actingAs($user)
        ->test(AppearanceSettings::class)
        ->set('selectedColor', 'not-a-color')
        ->call('save')
        ->assertHasErrors('selectedColor');

    expect($user->tenant->fresh()->brand_color)->toBeNull();
});
