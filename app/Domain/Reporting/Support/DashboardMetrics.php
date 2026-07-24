<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Support;

use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Restaurant\Enums\OrderStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\ArEntry;
use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\HousekeepingTask;
use App\Models\MaintenanceWorkOrder;
use App\Models\Reservation;
use App\Models\RestaurantOrder;
use App\Models\Room;

/**
 * Read-only aggregate queries for the FR-RPT-005 dashboard KPIs. Kept
 * separate from the Livewire component so the underlying figures are unit-
 * testable without a component render, and reusable later by the FR-RPT-001
 * standalone reports.
 */
class DashboardMetrics
{
    public function occupancyRate(int $branchId): float
    {
        $totalRooms = Room::where('branch_id', $branchId)->where('is_active', true)->count();

        if ($totalRooms === 0) {
            return 0.0;
        }

        $occupiedRooms = Room::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('status', RoomStatus::Occupied)
            ->count();

        return round(($occupiedRooms / $totalRooms) * 100, 1);
    }

    public function arrivalsToday(int $branchId): int
    {
        return Reservation::where('branch_id', $branchId)
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Confirmed])
            ->whereDate('arrival_date', now()->toDateString())
            ->count();
    }

    public function departuresToday(int $branchId): int
    {
        return Reservation::where('branch_id', $branchId)
            ->where('status', ReservationStatus::CheckedIn)
            ->whereDate('departure_date', now()->toDateString())
            ->count();
    }

    /**
     * @return array<string, int> RoomStatus label => room count, every
     *                            status represented (even at zero) so the
     *                            chart's legend/colors stay stable
     */
    public function roomStatusBreakdown(int $branchId): array
    {
        $counts = Room::where('branch_id', $branchId)
            ->where('is_active', true)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $breakdown = [];

        foreach (RoomStatus::cases() as $status) {
            $breakdown[$status->label()] = (int) ($counts[$status->value] ?? 0);
        }

        return $breakdown;
    }

    /**
     * Not yet completed — Pending, InProgress, AwaitingInspection, and
     * FailedInspection all still need housekeeping's attention today.
     */
    public function pendingHousekeepingCount(int $branchId): int
    {
        return HousekeepingTask::where('branch_id', $branchId)
            ->where('status', '!=', HousekeepingTaskStatus::Completed)
            ->count();
    }

    public function openMaintenanceCount(int $branchId): int
    {
        return MaintenanceWorkOrder::where('branch_id', $branchId)
            ->whereIn('status', [WorkOrderStatus::Open, WorkOrderStatus::InProgress])
            ->count();
    }

    /**
     * All of today's closed restaurant sales, dine-in and room-service
     * alike — a departmental figure independent of how the guest paid.
     */
    public function restaurantSalesTodayCents(int $branchId): int
    {
        return (int) RestaurantOrder::where('branch_id', $branchId)
            ->where('status', OrderStatus::Closed)
            ->whereDate('created_at', now()->toDateString())
            ->sum('total_cents');
    }

    /**
     * Guest folios still open with a balance owed, plus corporate/travel-
     * agent receivables not yet settled — the two places money can still be
     * outstanding to the hotel.
     */
    public function outstandingInvoicesCents(int $branchId): int
    {
        $folioBalance = (int) Folio::where('branch_id', $branchId)
            ->where('status', FolioStatus::Open)
            ->sum('balance_cents');

        $arOutstanding = ArEntry::where('branch_id', $branchId)
            ->whereIn('status', ['open', 'partially_paid'])
            ->get()
            ->sum(fn (ArEntry $entry) => $entry->outstandingCents());

        return $folioBalance + (int) $arOutstanding;
    }

    /**
     * Daily revenue over the trailing $days, oldest first. Combines folio
     * charges (room, restaurant-to-room, misc — anything billed to a guest
     * folio) with restaurant orders that were paid directly rather than
     * charged to a folio (RestaurantOrder::folio_id is only ever set when
     * CloseRestaurantOrderAction also posted a FolioCharge for it — see that
     * Action — so summing both here never double-counts the same sale).
     *
     * @return array<string, int> date (Y-m-d) => revenue in cents
     */
    public function revenueTrend(int $branchId, int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $trend = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $trend[$cursor->toDateString()] = 0;
            $cursor->addDay();
        }

        // charge_date is a DATE column, but on this app's SQLite setup it
        // round-trips with a midnight time component attached ("2026-07-24
        // 00:00:00"). Left as-is, that string sorts *after* a plain
        // "2026-07-24" upper bound, so a bare whereBetween silently drops
        // same-day rows; DATE() on both sides of the comparison (not just
        // the SELECT) avoids the mismatch on every driver.
        $folioRevenue = FolioCharge::whereHas('folio', fn ($query) => $query->where('branch_id', $branchId))
            ->whereRaw('DATE(charge_date) BETWEEN ? AND ?', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DATE(charge_date) as charge_day, sum(amount_cents) as total')
            ->groupBy('charge_day')
            ->pluck('total', 'charge_day');

        foreach ($folioRevenue as $date => $cents) {
            $key = (string) $date;

            if (array_key_exists($key, $trend)) {
                $trend[$key] += (int) $cents;
            }
        }

        $directRestaurantRevenue = RestaurantOrder::where('branch_id', $branchId)
            ->where('status', OrderStatus::Closed)
            ->whereNull('folio_id')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as sale_date, sum(total_cents) as total')
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        foreach ($directRestaurantRevenue as $date => $cents) {
            $key = (string) $date;

            if (array_key_exists($key, $trend)) {
                $trend[$key] += (int) $cents;
            }
        }

        return $trend;
    }
}
