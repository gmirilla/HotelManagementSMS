<?php

declare(strict_types=1);

namespace App\Livewire\Reporting;

use App\Domain\Reporting\Support\DashboardMetrics;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Support\Theme\BrandPalette;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-RPT-005: the dashboard surfaces real-time KPIs — occupancy rate,
 * today's arrivals/departures, room status breakdown, pending housekeeping,
 * open maintenance requests, today's restaurant sales, outstanding
 * invoices, and a revenue trend chart. Figures come from DashboardMetrics,
 * kept separate so they're testable without a component render and reusable
 * by the FR-RPT-001 standalone reports later.
 *
 * Colors used for the room-status doughnut mirror RoomManager's existing
 * status badges (emerald/amber/red/slate) so the same status always reads
 * the same color everywhere in the app; only "Occupied" ties to the
 * tenant's brand color, matching that badge's own brand-tinted styling.
 */
#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class DashboardOverview extends Component
{
    use InteractsWithActiveBranch;

    private const array ROOM_STATUS_COLORS = [
        '#10b981', // Vacant / Clean — emerald-500
        '#f59e0b', // Vacant / Dirty — amber-500
        null,      // Occupied — resolved from the tenant's brand ramp below
        '#ef4444', // Out of Order — red-500
        '#94a3b8', // Out of Service — slate-400
    ];

    #[Computed]
    public function occupancyRate(): float
    {
        return $this->branchId ? app(DashboardMetrics::class)->occupancyRate($this->branchId) : 0.0;
    }

    #[Computed]
    public function arrivalsToday(): int
    {
        return $this->branchId ? app(DashboardMetrics::class)->arrivalsToday($this->branchId) : 0;
    }

    #[Computed]
    public function departuresToday(): int
    {
        return $this->branchId ? app(DashboardMetrics::class)->departuresToday($this->branchId) : 0;
    }

    #[Computed]
    public function pendingHousekeepingCount(): int
    {
        return $this->branchId ? app(DashboardMetrics::class)->pendingHousekeepingCount($this->branchId) : 0;
    }

    #[Computed]
    public function openMaintenanceCount(): int
    {
        return $this->branchId ? app(DashboardMetrics::class)->openMaintenanceCount($this->branchId) : 0;
    }

    #[Computed]
    public function restaurantSalesTodayCents(): int
    {
        return $this->branchId ? app(DashboardMetrics::class)->restaurantSalesTodayCents($this->branchId) : 0;
    }

    #[Computed]
    public function outstandingInvoicesCents(): int
    {
        return $this->branchId ? app(DashboardMetrics::class)->outstandingInvoicesCents($this->branchId) : 0;
    }

    /**
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    #[Computed]
    public function roomStatusChartData(): array
    {
        $breakdown = $this->branchId ? app(DashboardMetrics::class)->roomStatusBreakdown($this->branchId) : [];
        $brandRamp = BrandPalette::ramp(auth()->user()->tenant?->brand_color);

        $colors = array_map(
            fn (?string $color) => $color ?? $brandRamp[600],
            self::ROOM_STATUS_COLORS,
        );

        return [
            'labels' => array_keys($breakdown),
            'datasets' => [[
                'data' => array_values($breakdown),
                'backgroundColor' => $colors,
                'borderWidth' => 0,
            ]],
        ];
    }

    /**
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    #[Computed]
    public function revenueTrendChartData(): array
    {
        $trend = $this->branchId ? app(DashboardMetrics::class)->revenueTrend($this->branchId) : [];
        $brandRamp = BrandPalette::ramp(auth()->user()->tenant?->brand_color);

        return [
            'labels' => array_map(fn (string $date) => Carbon::parse($date)->format('M j'), array_keys($trend)),
            'datasets' => [[
                'label' => 'Revenue',
                'data' => array_map(fn (int $cents) => round($cents / 100, 2), array_values($trend)),
                'borderColor' => $brandRamp[600],
                'backgroundColor' => $brandRamp[100],
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 2,
            ]],
        ];
    }

    public function render()
    {
        return view('livewire.reporting.dashboard-overview');
    }
}
