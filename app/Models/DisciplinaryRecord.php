<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\HR\Enums\DisciplinarySeverity;
use Database\Factories\DisciplinaryRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['employee_id', 'reported_by_user_id', 'incident_date', 'severity', 'description', 'action_taken'])]
class DisciplinaryRecord extends Model
{
    /** @use HasFactory<DisciplinaryRecordFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'severity' => DisciplinarySeverity::class,
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
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }
}
