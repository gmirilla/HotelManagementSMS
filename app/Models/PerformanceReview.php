<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\HR\Enums\PerformanceRating;
use Database\Factories\PerformanceReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['employee_id', 'reviewer_user_id', 'review_period', 'review_date', 'rating', 'strengths', 'areas_for_improvement', 'comments'])]
class PerformanceReview extends Model
{
    /** @use HasFactory<PerformanceReviewFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'rating' => PerformanceRating::class,
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
