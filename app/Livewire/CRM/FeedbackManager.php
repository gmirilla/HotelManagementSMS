<?php

declare(strict_types=1);

namespace App\Livewire\CRM;

use App\Domain\CRM\Actions\AssignGuestFeedbackAction;
use App\Domain\CRM\Actions\LogGuestFeedbackAction;
use App\Domain\CRM\Actions\ResolveGuestFeedbackAction;
use App\Domain\CRM\Enums\FeedbackType;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\GuestFeedback;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Guest Feedback')]
class FeedbackManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $guestSearch = '';

    public ?int $guestId = null;

    public string $type = 'complaint';

    public string $subject = '';

    public string $description = '';

    public ?int $resolvingId = null;

    public string $resolutionNotes = '';

    #[Computed]
    public function feedback(): Collection
    {
        return GuestFeedback::where('branch_id', $this->branchId)
            ->with(['guest', 'assignedTo'])
            ->orderByRaw("status = 'closed'")
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function guestResults(): Collection
    {
        if ($this->guestSearch === '') {
            return new Collection;
        }

        return Guest::where('tenant_id', auth()->user()->tenant_id)
            ->where(fn ($q) => $q->where('first_name', 'like', "%{$this->guestSearch}%")->orWhere('last_name', 'like', "%{$this->guestSearch}%"))
            ->limit(10)
            ->get();
    }

    public function create(): void
    {
        $this->authorize('create', GuestFeedback::class);

        $this->reset(['guestSearch', 'guestId', 'subject', 'description']);
        $this->type = 'complaint';
        $this->showForm = true;
    }

    public function save(LogGuestFeedbackAction $logFeedback): void
    {
        $this->authorize('create', GuestFeedback::class);

        $this->validate([
            'type' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $branch = Branch::findOrFail($this->branchId);
        $guest = $this->guestId ? Guest::find($this->guestId) : null;

        $logFeedback->handle($branch, $guest, FeedbackType::from($this->type), $this->subject, $this->description);

        $this->showForm = false;
        unset($this->feedback);
    }

    public function assignToMe(int $feedbackId, AssignGuestFeedbackAction $assignFeedback): void
    {
        $this->authorize('manage', GuestFeedback::class);

        $assignFeedback->handle(GuestFeedback::findOrFail($feedbackId), auth()->user());
        unset($this->feedback);
    }

    public function startResolve(int $feedbackId): void
    {
        $this->authorize('manage', GuestFeedback::class);

        $this->resolvingId = $feedbackId;
        $this->resolutionNotes = '';
    }

    public function resolve(ResolveGuestFeedbackAction $resolveFeedback): void
    {
        $this->authorize('manage', GuestFeedback::class);

        $this->validate(['resolutionNotes' => ['required', 'string', 'max:2000']]);

        $resolveFeedback->handle(GuestFeedback::findOrFail($this->resolvingId), $this->resolutionNotes);

        $this->resolvingId = null;
        unset($this->feedback);
    }

    public function render()
    {
        return view('livewire.crm.feedback-manager', ['types' => FeedbackType::cases()]);
    }
}
