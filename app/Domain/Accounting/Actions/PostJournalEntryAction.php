<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\Enums\JournalSide;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The sole write path for journal entries — every posting in the system,
 * whatever module triggers it, must go through here so the debit = credit
 * invariant can never be bypassed. MySQL can't express "the sum of this
 * entry's debit lines equals the sum of its credit lines" as a table
 * constraint, so it's enforced here instead (see coding-standards.md).
 */
class PostJournalEntryAction
{
    /**
     * @param  list<array{account_id: int, side: JournalSide|string, amount_cents: int}>  $lines
     */
    public function handle(
        int $branchId,
        Carbon $entryDate,
        array $lines,
        ?string $memo = null,
        ?User $createdBy = null,
        ?Model $reference = null,
    ): JournalEntry {
        if (count($lines) < 2) {
            throw ValidationException::withMessages(['lines' => __('A journal entry needs at least two lines.')]);
        }

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($lines as $line) {
            if ($line['amount_cents'] <= 0) {
                throw ValidationException::withMessages(['lines' => __('Every journal line amount must be greater than zero.')]);
            }

            $side = $line['side'] instanceof JournalSide ? $line['side'] : JournalSide::from($line['side']);

            if ($side === JournalSide::Debit) {
                $totalDebits += $line['amount_cents'];
            } else {
                $totalCredits += $line['amount_cents'];
            }
        }

        if ($totalDebits !== $totalCredits) {
            throw ValidationException::withMessages([
                'lines' => __('Debits (:debits) must equal credits (:credits).', [
                    'debits' => number_format($totalDebits / 100, 2),
                    'credits' => number_format($totalCredits / 100, 2),
                ]),
            ]);
        }

        return DB::transaction(function () use ($branchId, $entryDate, $lines, $memo, $createdBy, $reference) {
            $entry = JournalEntry::create([
                'branch_id' => $branchId,
                'entry_date' => $entryDate,
                'memo' => $memo,
                'created_by_user_id' => $createdBy?->id,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'side' => $line['side'] instanceof JournalSide ? $line['side'] : JournalSide::from($line['side']),
                    'amount_cents' => $line['amount_cents'],
                ]);
            }

            return $entry;
        });
    }
}
