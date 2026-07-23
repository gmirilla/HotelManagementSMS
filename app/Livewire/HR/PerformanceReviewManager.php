<?php

declare(strict_types=1);

namespace App\Livewire\HR;

use App\Domain\HR\Enums\PerformanceRating;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Employee;
use App\Models\PerformanceReview;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Performance Reviews')]
class PerformanceReviewManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $employeeId = '';

    public string $reviewPeriod = '';

    public string $reviewDate = '';

    public string $rating = 'meets_expectations';

    public string $strengths = '';

    public string $areasForImprovement = '';

    public string $comments = '';

    #[Computed]
    public function isHr(): bool
    {
        return auth()->user()->hasPermissionTo('hr.manage');
    }

    #[Computed]
    public function employees(): Collection
    {
        return Employee::where('branch_id', $this->branchId)->orderBy('last_name')->get();
    }

    #[Computed]
    public function reviews(): Collection
    {
        $myEmployeeId = auth()->user()->employee?->id;

        return PerformanceReview::whereHas('employee', fn ($q) => $q->where('branch_id', $this->branchId))
            ->when(! $this->isHr, fn ($q) => $q->where('employee_id', $myEmployeeId ?? 0))
            ->with(['employee', 'reviewer'])
            ->orderByDesc('review_date')
            ->get();
    }

    public function create(): void
    {
        $this->authorize('create', PerformanceReview::class);

        $this->reset(['employeeId', 'strengths', 'areasForImprovement', 'comments']);
        $this->reviewPeriod = now()->format('Y') . ' H' . (now()->month <= 6 ? '1' : '2');
        $this->reviewDate = now()->toDateString();
        $this->rating = 'meets_expectations';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', PerformanceReview::class);

        $this->validate([
            'employeeId' => ['required', 'integer'],
            'reviewPeriod' => ['required', 'string', 'max:50'],
            'reviewDate' => ['required', 'date'],
            'rating' => ['required', 'string'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'areasForImprovement' => ['nullable', 'string', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        PerformanceReview::create([
            'employee_id' => $this->employeeId,
            'reviewer_user_id' => auth()->id(),
            'review_period' => $this->reviewPeriod,
            'review_date' => $this->reviewDate,
            'rating' => $this->rating,
            'strengths' => $this->strengths ?: null,
            'areas_for_improvement' => $this->areasForImprovement ?: null,
            'comments' => $this->comments ?: null,
        ]);

        $this->showForm = false;
        unset($this->reviews);
    }

    public function render()
    {
        return view('livewire.hr.performance-review-manager', ['ratings' => PerformanceRating::cases()]);
    }
}
