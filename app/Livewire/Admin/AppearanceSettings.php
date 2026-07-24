<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Settings\Actions\UpdateTenantBrandColorAction;
use App\Support\Theme\BrandPalette;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Appearance')]
class AppearanceSettings extends Component
{
    public string $selectedColor = BrandPalette::DEFAULT_COLOR;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('settings.manage'), 403);
        // tenant_id is nullable at the schema level, so a user could in
        // principle be tenant-less; there's no per-tenant setting to manage
        // without one.
        abort_if(auth()->user()->tenant === null, 422);

        $this->selectedColor = auth()->user()->tenant->brand_color ?? BrandPalette::DEFAULT_COLOR;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function presets(): array
    {
        return BrandPalette::PRESETS;
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function ramp(): array
    {
        return BrandPalette::ramp($this->selectedColor);
    }

    public function selectPreset(string $hex): void
    {
        $this->selectedColor = $hex;
    }

    public function save(UpdateTenantBrandColorAction $updateBrandColor): void
    {
        abort_unless(auth()->user()->hasPermissionTo('settings.manage'), 403);

        $this->validate([
            'selectedColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $updateBrandColor->handle(auth()->user()->tenant, $this->selectedColor);

        // A full redirect (rather than an in-place Livewire re-render) so the
        // <style> override injected into the layout's <head> recomputes with
        // the newly saved color — that markup lives outside this component's
        // own render tree.
        $this->redirect(route('admin.appearance'));
    }

    public function render()
    {
        return view('livewire.admin.appearance-settings');
    }
}
