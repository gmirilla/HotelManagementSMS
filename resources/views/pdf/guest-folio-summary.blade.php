<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Guest &amp; Folio Summary</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #64748b; }
        .header { margin-bottom: 16px; }
        .summary { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .summary td { padding: 6px 10px; border: 1px solid #e2e8f0; }
        .summary .label { font-size: 9px; text-transform: uppercase; color: #64748b; display: block; }
        .summary .value { font-size: 13px; font-weight: bold; }
        table.entries { width: 100%; border-collapse: collapse; }
        table.entries th, table.entries td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        table.entries th { text-transform: uppercase; font-size: 8px; color: #64748b; }
        td.amount, th.amount { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $branch?->name ?? 'All Branches' }}</h1>
        <p class="muted">Guest &amp; Folio Summary &mdash; arrivals {{ $startDate }} to {{ $endDate }}</p>
    </div>

    <table class="summary">
        <tr>
            <td><span class="label">Total Guests</span><span class="value">{{ $summary['guest_count'] }}</span></td>
            <td><span class="label">Reservations</span><span class="value">{{ $summary['reservation_count'] }}</span></td>
            <td><span class="label">Charges</span><span class="value">{{ number_format($summary['charges_total_cents'] / 100, 2) }}</span></td>
            <td><span class="label">Payments</span><span class="value">{{ number_format($summary['payments_total_cents'] / 100, 2) }}</span></td>
            <td><span class="label">Outstanding</span><span class="value">{{ number_format($summary['balance_total_cents'] / 100, 2) }}</span></td>
        </tr>
    </table>

    <table class="entries">
        <thead>
            <tr>
                <th>Guest</th>
                <th>Room(s)</th>
                <th>Arrival</th>
                <th>Departure</th>
                <th>Folio Status</th>
                <th class="amount">Charges</th>
                <th class="amount">Payments</th>
                <th class="amount">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reservations as $reservation)
                <tr>
                    <td>{{ $reservation->guest->fullName() }}</td>
                    <td>{{ $reservation->rooms->pluck('room.room_number')->filter()->implode(', ') }}</td>
                    <td>{{ $reservation->arrival_date->format('M j, Y') }}</td>
                    <td>{{ $reservation->departure_date->format('M j, Y') }}</td>
                    <td>{{ $reservation->folio ? ucfirst($reservation->folio->status->value) : 'No folio' }}</td>
                    <td class="amount">{{ number_format(($reservation->folio->charges_total_cents ?? 0) / 100, 2) }}</td>
                    <td class="amount">{{ number_format(($reservation->folio->payments_total_cents ?? 0) / 100, 2) }}</td>
                    <td class="amount">{{ number_format(($reservation->folio->balance_cents ?? 0) / 100, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No guests in this date range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
