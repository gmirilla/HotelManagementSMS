<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Payroll</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>
    </div>

    @can('hr.manage')
        <form wire:submit="process" class="mb-6 flex flex-wrap items-end gap-4 rounded-lg border border-slate-200 bg-white p-6">
            <div>
                <x-input-label value="Period start" />
                <x-text-input type="date" wire:model="periodStart" />
                <x-input-error :messages="$errors->get('periodStart')" />
            </div>
            <div>
                <x-input-label value="Period end" />
                <x-text-input type="date" wire:model="periodEnd" />
                <x-input-error :messages="$errors->get('periodEnd')" />
            </div>
            <x-primary-button class="w-auto">Process payroll run</x-primary-button>
        </form>
    @endcan

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Period</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Payslips</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->payrollRuns as $run)
                        <tr wire:key="run-{{ $run->id }}">
                            <td class="px-4 py-2 text-slate-800">{{ $run->period_start->format('M j') }} – {{ $run->period_end->format('M j, Y') }}</td>
                            <td class="px-4 py-2">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst($run->status->value) }}</span>
                            </td>
                            <td class="px-4 py-2 text-slate-600">{{ $run->payslips_count }}</td>
                            <td class="px-4 py-2 text-right">
                                <button wire:click="view({{ $run->id }})" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No payroll runs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            @if ($this->viewingRun)
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="font-medium text-slate-800">
                            Payslips — {{ $this->viewingRun->period_start->format('M j') }} – {{ $this->viewingRun->period_end->format('M j, Y') }}
                        </p>
                    </div>
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-3">Employee</th><th class="px-4 py-3">Gross</th><th class="px-4 py-3">Deductions</th><th class="px-4 py-3">Net</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($this->viewingRun->payslips as $payslip)
                                <tr wire:key="payslip-{{ $payslip->id }}">
                                    <td class="px-4 py-2 text-slate-800">{{ $payslip->employee->fullName() }}</td>
                                    <td class="px-4 py-2 text-slate-600">${{ number_format($payslip->gross_cents / 100, 2) }}</td>
                                    <td class="px-4 py-2 text-red-600">-${{ number_format($payslip->deductions_cents / 100, 2) }}</td>
                                    <td class="px-4 py-2 font-medium text-slate-800">${{ number_format($payslip->net_cents / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-slate-500">Select a payroll run to view its payslips.</p>
            @endif
        </div>
    </div>
</div>
