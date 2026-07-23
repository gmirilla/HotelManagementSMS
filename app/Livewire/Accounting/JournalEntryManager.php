<?php

declare(strict_types=1);

namespace App\Livewire\Accounting;

use App\Domain\Accounting\Actions\PostJournalEntryAction;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Journal Entries')]
class JournalEntryManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $entryDate = '';

    public string $memo = '';

    /** @var array<int, array{account_id: string, side: string, amount: string}> */
    public array $lines = [
        ['account_id' => '', 'side' => 'debit', 'amount' => ''],
        ['account_id' => '', 'side' => 'credit', 'amount' => ''],
    ];

    public function mount(): void
    {
        $this->entryDate = now()->toDateString();
    }

    #[Computed]
    public function journalEntries(): Collection
    {
        return JournalEntry::where('branch_id', $this->branchId)
            ->with('lines.account', 'createdBy')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function accounts(): Collection
    {
        return Account::where('branch_id', $this->branchId)->where('is_active', true)->orderBy('code')->get();
    }

    public function create(): void
    {
        $this->authorize('create', JournalEntry::class);

        $this->reset(['memo']);
        $this->entryDate = now()->toDateString();
        $this->lines = [
            ['account_id' => '', 'side' => 'debit', 'amount' => ''],
            ['account_id' => '', 'side' => 'credit', 'amount' => ''],
        ];
        $this->showForm = true;
    }

    public function addLine(): void
    {
        $this->lines[] = ['account_id' => '', 'side' => 'debit', 'amount' => ''];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save(PostJournalEntryAction $postJournalEntry): void
    {
        $this->authorize('create', JournalEntry::class);

        $this->validate([
            'entryDate' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer'],
            'lines.*.side' => ['required', 'in:debit,credit'],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $lines = collect($this->lines)->map(fn (array $line) => [
            'account_id' => (int) $line['account_id'],
            'side' => $line['side'],
            'amount_cents' => (int) round(((float) $line['amount']) * 100),
        ])->all();

        $postJournalEntry->handle(
            $this->branchId,
            Carbon::parse($this->entryDate),
            $lines,
            $this->memo ?: null,
            auth()->user(),
        );

        $this->showForm = false;
        unset($this->journalEntries);
    }

    public function render()
    {
        return view('livewire.accounting.journal-entry-manager');
    }
}
