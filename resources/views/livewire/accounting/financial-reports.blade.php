<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Financial Reports</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        @if ($this->accessibleBranches->count() > 1)
            <select wire:model.live="branchId" class="rounded-md border-slate-300 text-sm shadow-sm">
                @foreach ($this->accessibleBranches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div class="mb-6 flex gap-1 rounded-lg bg-slate-100 p-1 text-sm font-medium">
        <button wire:click="$set('tab', 'trial_balance')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'trial_balance', 'text-slate-500' => $tab !== 'trial_balance'])>
            Trial Balance
        </button>
        <button wire:click="$set('tab', 'profit_loss')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'profit_loss', 'text-slate-500' => $tab !== 'profit_loss'])>
            Profit &amp; Loss
        </button>
        <button wire:click="$set('tab', 'balance_sheet')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'balance_sheet', 'text-slate-500' => $tab !== 'balance_sheet'])>
            Balance Sheet
        </button>
    </div>

    @if ($tab === 'trial_balance' || $tab === 'profit_loss')
        <div class="mb-4 flex items-end gap-3">
            <div>
                <x-input-label value="From" />
                <x-text-input type="date" wire:model.live="startDate" />
            </div>
            <div>
                <x-input-label value="To" />
                <x-text-input type="date" wire:model.live="endDate" />
            </div>
        </div>
    @else
        <div class="mb-4">
            <x-input-label value="As of" />
            <x-text-input type="date" wire:model.live="asOfDate" />
        </div>
    @endif

    @if ($tab === 'trial_balance')
        <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Account</th><th class="px-4 py-3 text-right">Debit</th><th class="px-4 py-3 text-right">Credit</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($this->trialBalance as $row)
                        <tr>
                            <td class="px-4 py-2 text-slate-700">{{ $row['account']->code }} — {{ $row['account']->name }}</td>
                            <td class="px-4 py-2 text-right text-slate-800">{{ $row['debit_cents'] > 0 ? number_format($row['debit_cents'] / 100, 2) : '' }}</td>
                            <td class="px-4 py-2 text-right text-slate-800">{{ $row['credit_cents'] > 0 ? number_format($row['credit_cents'] / 100, 2) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-300 font-semibold">
                    <tr>
                        <td class="px-4 py-2">Total</td>
                        <td class="px-4 py-2 text-right">{{ number_format($this->trialBalanceTotals['debit'] / 100, 2) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($this->trialBalanceTotals['credit'] / 100, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    @if ($tab === 'profit_loss')
        @php $pnl = $this->profitAndLoss; @endphp
        <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
            <h2 class="mb-2 text-sm font-semibold uppercase text-slate-500">Revenue</h2>
            @foreach ($pnl['revenue'] as $row)
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-slate-600">{{ $row['account']->name }}</span>
                    <span class="text-slate-800">{{ number_format($row['amount_cents'] / 100, 2) }}</span>
                </div>
            @endforeach
            <div class="flex justify-between border-t border-slate-200 py-2 text-sm font-semibold">
                <span>Total revenue</span><span>{{ number_format($pnl['total_revenue_cents'] / 100, 2) }}</span>
            </div>

            <h2 class="mb-2 mt-4 text-sm font-semibold uppercase text-slate-500">Expenses</h2>
            @foreach ($pnl['expenses'] as $row)
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-slate-600">{{ $row['account']->name }}</span>
                    <span class="text-slate-800">{{ number_format($row['amount_cents'] / 100, 2) }}</span>
                </div>
            @endforeach
            <div class="flex justify-between border-t border-slate-200 py-2 text-sm font-semibold">
                <span>Total expenses</span><span>{{ number_format($pnl['total_expense_cents'] / 100, 2) }}</span>
            </div>

            <div class="mt-4 flex justify-between border-t-2 border-slate-300 py-2 text-base font-bold">
                <span>Net income</span>
                <span class="{{ $pnl['net_income_cents'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ number_format($pnl['net_income_cents'] / 100, 2) }}
                </span>
            </div>
        </div>
    @endif

    @if ($tab === 'balance_sheet')
        @php $bs = $this->balanceSheet; @endphp
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <h2 class="mb-2 text-sm font-semibold uppercase text-slate-500">Assets</h2>
                @foreach ($bs['assets'] as $row)
                    <div class="flex justify-between py-1 text-sm">
                        <span class="text-slate-600">{{ $row['account']->name }}</span>
                        <span class="text-slate-800">{{ number_format($row['amount_cents'] / 100, 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between border-t border-slate-200 py-2 text-sm font-semibold">
                    <span>Total assets</span><span>{{ number_format($bs['total_assets_cents'] / 100, 2) }}</span>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <h2 class="mb-2 text-sm font-semibold uppercase text-slate-500">Liabilities</h2>
                @foreach ($bs['liabilities'] as $row)
                    <div class="flex justify-between py-1 text-sm">
                        <span class="text-slate-600">{{ $row['account']->name }}</span>
                        <span class="text-slate-800">{{ number_format($row['amount_cents'] / 100, 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between border-t border-slate-200 py-2 text-sm font-semibold">
                    <span>Total liabilities</span><span>{{ number_format($bs['total_liabilities_cents'] / 100, 2) }}</span>
                </div>

                <h2 class="mb-2 mt-4 text-sm font-semibold uppercase text-slate-500">Equity</h2>
                @foreach ($bs['equity'] as $row)
                    <div class="flex justify-between py-1 text-sm">
                        <span class="text-slate-600">{{ $row['account']->name }}</span>
                        <span class="text-slate-800">{{ number_format($row['amount_cents'] / 100, 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between border-t border-slate-200 py-2 text-sm font-semibold">
                    <span>Total equity</span><span>{{ number_format($bs['total_equity_cents'] / 100, 2) }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
