<?php

declare(strict_types=1);

namespace App\Livewire\HR;

use App\Domain\HR\Enums\CandidateStage;
use App\Domain\HR\Enums\JobOpeningStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Candidate;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Recruitment')]
class RecruitmentBoard extends Component
{
    use InteractsWithActiveBranch;

    public bool $showOpeningForm = false;

    public string $title = '';

    public string $department = '';

    public string $description = '';

    public ?int $selectedOpeningId = null;

    public bool $showCandidateForm = false;

    public string $candidateName = '';

    public string $candidateEmail = '';

    public string $candidatePhone = '';

    #[Computed]
    public function jobOpenings(): Collection
    {
        return JobOpening::where('branch_id', $this->branchId)->withCount('candidates')->orderByDesc('created_at')->get();
    }

    #[Computed]
    public function selectedOpening(): ?JobOpening
    {
        if (! $this->selectedOpeningId) {
            return null;
        }

        return JobOpening::with('candidates')->find($this->selectedOpeningId);
    }

    public function select(int $openingId): void
    {
        $this->selectedOpeningId = $openingId;
    }

    public function createOpening(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        $this->reset(['title', 'department', 'description']);
        $this->showOpeningForm = true;
    }

    public function saveOpening(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $opening = JobOpening::create([
            'branch_id' => $this->branchId,
            'title' => $this->title,
            'department' => $this->department,
            'description' => $this->description ?: null,
        ]);

        $this->showOpeningForm = false;
        $this->selectedOpeningId = $opening->id;
        unset($this->jobOpenings);
    }

    public function closeOpening(int $openingId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        JobOpening::findOrFail($openingId)->update(['status' => JobOpeningStatus::Closed, 'closed_at' => now()]);
        unset($this->jobOpenings);
    }

    public function addCandidate(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        $this->reset(['candidateName', 'candidateEmail', 'candidatePhone']);
        $this->showCandidateForm = true;
    }

    public function saveCandidate(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        $this->validate([
            'candidateName' => ['required', 'string', 'max:255'],
            'candidateEmail' => ['required', 'email', 'max:255'],
            'candidatePhone' => ['nullable', 'string', 'max:50'],
        ]);

        Candidate::create([
            'job_opening_id' => $this->selectedOpeningId,
            'name' => $this->candidateName,
            'email' => $this->candidateEmail,
            'phone' => $this->candidatePhone ?: null,
        ]);

        $this->showCandidateForm = false;
        unset($this->jobOpenings);
    }

    public function advanceStage(int $candidateId, string $stage): void
    {
        abort_unless(auth()->user()->hasPermissionTo('hr.manage'), 403);

        Candidate::findOrFail($candidateId)->update(['stage' => CandidateStage::from($stage)]);
    }

    public function render()
    {
        return view('livewire.hr.recruitment-board', ['stages' => CandidateStage::cases()]);
    }
}
