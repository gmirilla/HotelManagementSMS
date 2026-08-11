<?php

declare(strict_types=1);

namespace App\Livewire\Guests;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Guest & Folio Summary')]
class GuestFolioSummary extends Component
{
    use InteractsWithActiveBranch;

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function authorizeView(): void
    {
        $user = auth()->user();

        abort_unless(
            $user->hasAnyPermission(['guests.view', 'guests.manage'])
                && $user->hasAnyPermission(['folios.view', 'folios.manage']),
            403,
        );
    }

    /**
     * @return Collection<int, Reservation>
     */
    #[Computed]
    public function reservations(): Collection
    {
        $this->authorizeView();

        return Reservation::where('branch_id', $this->branchId)
            ->whereBetween('arrival_date', [$this->startDate, $this->endDate])
            ->with([
                'guest',
                'rooms.room',
                'folio' => fn ($query) => $query
                    ->withSum('charges as charges_total_cents', 'amount_cents')
                    ->withSum(['payments as payments_total_cents' => fn ($q) => $q->where('status', PaymentStatus::Completed)], 'amount_cents'),
            ])
            ->orderBy('arrival_date')
            ->get();
    }

    /**
     * @return array{guest_count: int, reservation_count: int, charges_total_cents: int, payments_total_cents: int, balance_total_cents: int}
     */
    #[Computed]
    public function summary(): array
    {
        $reservations = $this->reservations;

        return [
            'guest_count' => $reservations->pluck('guest_id')->unique()->count(),
            'reservation_count' => $reservations->count(),
            'charges_total_cents' => $reservations->sum(fn (Reservation $r) => $r->folio?->charges_total_cents ?? 0),
            'payments_total_cents' => $reservations->sum(fn (Reservation $r) => $r->folio?->payments_total_cents ?? 0),
            'balance_total_cents' => $reservations->sum(fn (Reservation $r) => $r->folio?->balance_cents ?? 0),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorizeView();

        $reservations = $this->reservations;
        $summary = $this->summary;

        return response()->streamDownload(function () use ($reservations, $summary): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Guest', 'Room(s)', 'Arrival', 'Departure', 'Folio Status', 'Charges', 'Payments', 'Balance']);

            foreach ($reservations as $reservation) {
                fputcsv($handle, $this->csvRow($reservation));
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Total guests', $summary['guest_count']]);
            fputcsv($handle, ['Total reservations', $summary['reservation_count']]);
            fputcsv($handle, ['Total charges', number_format($summary['charges_total_cents'] / 100, 2, '.', '')]);
            fputcsv($handle, ['Total payments', number_format($summary['payments_total_cents'] / 100, 2, '.', '')]);
            fputcsv($handle, ['Total outstanding balance', number_format($summary['balance_total_cents'] / 100, 2, '.', '')]);

            fclose($handle);
        }, "guest-folio-summary-{$this->startDate}_to_{$this->endDate}.csv", ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(): StreamedResponse
    {
        $this->authorizeView();

        $pdf = Pdf::loadView('pdf.guest-folio-summary', [
            'branch' => $this->activeBranch,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'reservations' => $this->reservations,
            'summary' => $this->summary,
        ]);

        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf->output();
            },
            "guest-folio-summary-{$this->startDate}_to_{$this->endDate}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @return array<int, string>
     */
    private function csvRow(Reservation $reservation): array
    {
        return [
            $reservation->guest->fullName(),
            $reservation->rooms->pluck('room.room_number')->filter()->implode(', '),
            $reservation->arrival_date->format('Y-m-d'),
            $reservation->departure_date->format('Y-m-d'),
            $reservation->folio ? ucfirst($reservation->folio->status->value) : 'No folio',
            number_format(($reservation->folio->charges_total_cents ?? 0) / 100, 2, '.', ''),
            number_format(($reservation->folio->payments_total_cents ?? 0) / 100, 2, '.', ''),
            number_format(($reservation->folio->balance_cents ?? 0) / 100, 2, '.', ''),
        ];
    }

    public function render()
    {
        return view('livewire.guests.guest-folio-summary');
    }
}
