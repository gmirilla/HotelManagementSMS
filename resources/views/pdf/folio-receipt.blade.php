<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt — Folio #{{ $folio->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #64748b; }
        .header { margin-bottom: 20px; }
        .totals { margin-top: 10px; text-align: right; }
        .totals .balance { font-size: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        th { text-transform: uppercase; font-size: 9px; color: #64748b; }
        td.amount, th.amount { text-align: right; }
        .section-title { margin-top: 20px; font-size: 13px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $folio->branch->name }}</h1>
        <p class="muted">Checkout Receipt — Folio #{{ $folio->id }}</p>
    </div>

    <table style="border: none;">
        <tr style="border: none;">
            <td style="border: none; width: 50%;">
                <strong>Guest</strong><br>
                {{ $folio->guest->fullName() }}
            </td>
            <td style="border: none; width: 50%;">
                @if ($folio->reservation)
                    <strong>Reservation</strong><br>
                    {{ $folio->reservation->confirmation_code }}<br>
                    {{ $folio->reservation->arrival_date->format('M j, Y') }} &ndash; {{ $folio->reservation->departure_date->format('M j, Y') }}
                @endif
            </td>
        </tr>
    </table>

    <div class="section-title">Charges</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($folio->charges as $charge)
                <tr>
                    <td>{{ $charge->charge_date->format('M j, Y') }}</td>
                    <td>{{ $charge->description }}</td>
                    <td class="amount">{{ number_format($charge->amount_cents / 100, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No charges.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Payments</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Method</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($folio->payments as $payment)
                <tr>
                    <td>{{ $payment->created_at->format('M j, Y') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $payment->method->value)) }}</td>
                    <td class="amount">{{ number_format($payment->amount_cents / 100, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No payments.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <p class="balance">Balance: {{ number_format($folio->balance_cents / 100, 2) }}</p>
        @if ($folio->closed_at)
            <p class="muted">Closed {{ $folio->closed_at->format('M j, Y g:i A') }}</p>
        @endif
    </div>
</body>
</html>
