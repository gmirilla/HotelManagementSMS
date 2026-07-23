<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Guest\Enums\DocumentType;
use Database\Factories\GuestDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['guest_id', 'document_type', 'document_number', 'issuing_country', 'expires_on'])]
class GuestDocument extends Model
{
    /** @use HasFactory<GuestDocumentFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'document_number' => 'encrypted',
            'expires_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
