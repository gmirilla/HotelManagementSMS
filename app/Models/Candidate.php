<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\HR\Enums\CandidateStage;
use Database\Factories\CandidateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['job_opening_id', 'name', 'email', 'phone', 'stage', 'notes'])]
class Candidate extends Model
{
    /** @use HasFactory<CandidateFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'stage' => CandidateStage::class,
        ];
    }

    /**
     * @return BelongsTo<JobOpening, $this>
     */
    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }
}
