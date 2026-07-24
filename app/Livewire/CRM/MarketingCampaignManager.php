<?php

declare(strict_types=1);

namespace App\Livewire\CRM;

use App\Domain\CRM\Enums\MarketingCampaignChannel;
use App\Domain\CRM\Enums\MarketingCampaignStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Marketing Campaigns')]
class MarketingCampaignManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $name = '';

    public string $channel = 'email';

    public string $message = '';

    public string $scheduledAt = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string'],
            'message' => ['required', 'string', 'max:2000'],
            'scheduledAt' => ['nullable', 'date'],
        ];
    }

    #[Computed]
    public function campaigns(): Collection
    {
        return MarketingCampaign::where('branch_id', $this->branchId)->orderByDesc('created_at')->get();
    }

    public function create(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('crm.manage'), 403);

        $this->reset(['name', 'message', 'scheduledAt']);
        $this->channel = 'email';
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('crm.manage'), 403);
        $this->validate();

        MarketingCampaign::create([
            'branch_id' => $this->branchId,
            'name' => $this->name,
            'channel' => $this->channel,
            'message' => $this->message,
            'status' => $this->scheduledAt !== '' && $this->scheduledAt !== '0' ? MarketingCampaignStatus::Scheduled : MarketingCampaignStatus::Draft,
            'scheduled_at' => $this->scheduledAt ?: null,
        ]);

        $this->showForm = false;
        unset($this->campaigns);
    }

    public function markSent(int $campaignId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('crm.manage'), 403);

        MarketingCampaign::findOrFail($campaignId)->update(['status' => MarketingCampaignStatus::Sent, 'sent_at' => now()]);
        unset($this->campaigns);
    }

    public function render()
    {
        return view('livewire.crm.marketing-campaign-manager', ['channels' => MarketingCampaignChannel::cases()]);
    }
}
