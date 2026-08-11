<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>
        @if ($tab === 'trial_balance') Trial Balance
        @elseif ($tab === 'profit_loss') Profit &amp; Loss
        @else Balance Sheet
        @endif
    </title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #64748b; }
        .header { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        th { text-transform: uppercase; font-size: 9px; color: #64748b; }
        td.amount, th.amount { text-align: right; }
        tfoot td { font-weight: bold; border-top: 2px solid #94a3b8; border-bottom: none; }
        .section-title { margin-top: 20px; font-size: 13px; font-weight: bold; }
        .total-row { font-weight: bold; border-top: 1px solid #94a3b8; }
        .net-income { margin-top: 16px; font-size: 14px; font-weight: bold; border-top: 2px solid #94a3b8; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch?->name ?? 'All Branches' }}</h1>
        <p class="muted">
            @if ($tab === 'trial_balance')
                Trial Balance &mdash; {{ $startDate }} to {{ $endDate }}
            @elseif ($tab === 'profit_loss')
                Profit &amp; Loss &mdash; {{ $startDate }} to {{ $endDate }}
            @else
                Balance Sheet &mdash; as of {{ $asOfDate }}
            @endif
        </p>
    </div>

    @if ($tab === 'trial_balance')
        <table>
            <thead>
                <tr><th>Account</th><th class="amount">Debit</th><th class="amount">Credit</th></tr>
            </thead>
            <tbody>
                @foreach ($trialBalance as $row)
                    <tr>
                        <td>{{ $row['account']->code }} &mdash; {{ $row['account']->name }}</td>
                        <td class="amount">{{ $row['debit_cents'] > 0 ? number_format($row['debit_cents'] / 100, 2) : '' }}</td>
                        <td class="amount">{{ $row['credit_cents'] > 0 ? number_format($row['credit_cents'] / 100, 2) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="amount">{{ number_format($trialBalanceTotals['debit'] / 100, 2) }}</td>
                    <td class="amount">{{ number_format($trialBalanceTotals['credit'] / 100, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    @if ($tab === 'profit_loss')
        <div class="section-title">Revenue</div>
        <table>
            <tbody>
                @foreach ($profitAndLoss['revenue'] as $row)
                    <tr><td>{{ $row['account']->name }}</td><td class="amount">{{ number_format($row['amount_cents'] / 100, 2) }}</td></tr>
                @endforeach
                <tr class="total-row"><td>Total revenue</td><td class="amount">{{ number_format($profitAndLoss['total_revenue_cents'] / 100, 2) }}</td></tr>
            </tbody>
        </table>

        <div class="section-title">Expenses</div>
        <table>
            <tbody>
                @foreach ($profitAndLoss['expenses'] as $row)
                    <tr><td>{{ $row['account']->name }}</td><td class="amount">{{ number_format($row['amount_cents'] / 100, 2) }}</td></tr>
                @endforeach
                <tr class="total-row"><td>Total expenses</td><td class="amount">{{ number_format($profitAndLoss['total_expense_cents'] / 100, 2) }}</td></tr>
            </tbody>
        </table>

        <div class="net-income">
            Net income: {{ number_format($profitAndLoss['net_income_cents'] / 100, 2) }}
        </div>
    @endif

    @if ($tab === 'balance_sheet')
        <div class="section-title">Assets</div>
        <table>
            <tbody>
                @foreach ($balanceSheet['assets'] as $row)
                    <tr><td>{{ $row['account']->name }}</td><td class="amount">{{ number_format($row['amount_cents'] / 100, 2) }}</td></tr>
                @endforeach
                <tr class="total-row"><td>Total assets</td><td class="amount">{{ number_format($balanceSheet['total_assets_cents'] / 100, 2) }}</td></tr>
            </tbody>
        </table>

        <div class="section-title">Liabilities</div>
        <table>
            <tbody>
                @foreach ($balanceSheet['liabilities'] as $row)
                    <tr><td>{{ $row['account']->name }}</td><td class="amount">{{ number_format($row['amount_cents'] / 100, 2) }}</td></tr>
                @endforeach
                <tr class="total-row"><td>Total liabilities</td><td class="amount">{{ number_format($balanceSheet['total_liabilities_cents'] / 100, 2) }}</td></tr>
            </tbody>
        </table>

        <div class="section-title">Equity</div>
        <table>
            <tbody>
                @foreach ($balanceSheet['equity'] as $row)
                    <tr><td>{{ $row['account']->name }}</td><td class="amount">{{ number_format($row['amount_cents'] / 100, 2) }}</td></tr>
                @endforeach
                <tr class="total-row"><td>Total equity</td><td class="amount">{{ number_format($balanceSheet['total_equity_cents'] / 100, 2) }}</td></tr>
            </tbody>
        </table>
    @endif
</body>
</html>
