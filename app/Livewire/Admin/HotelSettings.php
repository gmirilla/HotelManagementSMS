<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Settings\Actions\UpdateHotelSettingsAction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Hotel Settings')]
class HotelSettings extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $defaultCurrency = '';

    public string $defaultTimezone = '';

    public mixed $logo = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('settings.manage'), 403);
        abort_if(auth()->user()->tenant === null, 422);

        $tenant = auth()->user()->tenant;
        $this->name = $tenant->name;
        $this->defaultCurrency = $tenant->default_currency;
        $this->defaultTimezone = $tenant->default_timezone;
    }

    public function save(UpdateHotelSettingsAction $updateHotelSettings): void
    {
        abort_unless(auth()->user()->hasPermissionTo('settings.manage'), 403);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3', 'alpha'],
            'defaultTimezone' => ['required', 'string', 'timezone'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $updateHotelSettings->handle(
            auth()->user()->tenant,
            $this->name,
            strtoupper($this->defaultCurrency),
            $this->defaultTimezone,
            $this->logo,
        );

        session()->flash('status', __('Hotel settings updated.'));

        // A full redirect, not an in-place re-render: the top bar shows the
        // tenant name/logo as part of the outer layout, outside this
        // component's own render tree (same reasoning as AppearanceSettings
        // redirecting after a brand-color change).
        $this->redirect(route('admin.hotel-settings'));
    }

    public function render()
    {
        return view('livewire.admin.hotel-settings', [
            'tenant' => auth()->user()->tenant,
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }
}
