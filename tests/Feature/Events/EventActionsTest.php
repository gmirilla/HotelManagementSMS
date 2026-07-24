<?php

declare(strict_types=1);

use App\Domain\Event\Actions\AddEventBookingItemAction;
use App\Domain\Event\Actions\CancelEventBookingAction;
use App\Domain\Event\Actions\ConfirmEventBookingAction;
use App\Domain\Event\Actions\CreateEventBookingAction;
use App\Domain\Event\Enums\EventBookingStatus;
use App\Domain\Event\Support\EventBillCalculator;
use App\Models\Branch;
use App\Models\EventService;
use App\Models\EventSpace;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

test('creating a booking rejects an end time before the start time', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id]);

    app(CreateEventBookingAction::class)->handle(
        $branch, $space, 'Test Event', 'conference',
        Carbon::parse('2026-08-01 14:00'), Carbon::parse('2026-08-01 10:00'),
    );
})->throws(ValidationException::class);

test('creating a booking rejects an overlapping time window in the same space', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id]);

    app(CreateEventBookingAction::class)->handle(
        $branch, $space, 'First Event', 'conference',
        Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'),
    );

    app(CreateEventBookingAction::class)->handle(
        $branch, $space, 'Second Event', 'conference',
        Carbon::parse('2026-08-01 12:00'), Carbon::parse('2026-08-01 16:00'),
    );
})->throws(ValidationException::class);

test('a non-overlapping booking in the same space is allowed', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id]);

    app(CreateEventBookingAction::class)->handle(
        $branch, $space, 'First Event', 'conference',
        Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'),
    );

    $second = app(CreateEventBookingAction::class)->handle(
        $branch, $space, 'Second Event', 'conference',
        Carbon::parse('2026-08-01 13:00'), Carbon::parse('2026-08-01 17:00'),
    );

    expect($second->status)->toBe(EventBookingStatus::Tentative);
});

test('a cancelled booking frees the space for a new overlapping booking', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id]);

    $first = app(CreateEventBookingAction::class)->handle(
        $branch, $space, 'First Event', 'conference',
        Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'),
    );
    app(CancelEventBookingAction::class)->handle($first);

    $second = app(CreateEventBookingAction::class)->handle(
        $branch, $space, 'Second Event', 'conference',
        Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'),
    );

    expect($second->status)->toBe(EventBookingStatus::Tentative);
});

test('confirming a booking flips it from tentative to confirmed', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id]);
    $booking = app(CreateEventBookingAction::class)->handle($branch, $space, 'Event', 'conference', Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'));

    app(ConfirmEventBookingAction::class)->handle($booking);

    expect($booking->fresh()->status)->toBe(EventBookingStatus::Confirmed);
});

test('a confirmed booking cannot be confirmed again', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id]);
    $booking = app(CreateEventBookingAction::class)->handle($branch, $space, 'Event', 'conference', Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'));
    app(ConfirmEventBookingAction::class)->handle($booking);

    app(ConfirmEventBookingAction::class)->handle($booking);
})->throws(ValidationException::class);

test('a completed booking cannot be cancelled', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id]);
    $booking = app(CreateEventBookingAction::class)->handle($branch, $space, 'Event', 'conference', Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'));
    $booking->update(['status' => EventBookingStatus::Completed]);

    app(CancelEventBookingAction::class)->handle($booking);
})->throws(ValidationException::class);

test('adding a booking item snapshots the service price at add-time', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id]);
    $booking = app(CreateEventBookingAction::class)->handle($branch, $space, 'Event', 'conference', Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'));
    $service = EventService::factory()->create(['branch_id' => $branch->id, 'unit_price_cents' => 2500]);

    $item = app(AddEventBookingItemAction::class)->handle($booking, $service, 10);

    // Changing the service price afterwards must not retroactively change the booked item.
    $service->update(['unit_price_cents' => 9999]);

    expect($item->unit_price_cents)->toBe(2500)
        ->and($item->lineTotalCents())->toBe(25000);
});

test('adding a zero or negative quantity item is rejected', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id]);
    $booking = app(CreateEventBookingAction::class)->handle($branch, $space, 'Event', 'conference', Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'));
    $service = EventService::factory()->create(['branch_id' => $branch->id]);

    app(AddEventBookingItemAction::class)->handle($booking, $service, 0);
})->throws(ValidationException::class);

test('the consolidated bill sums venue rental and every service line item', function (): void {
    $branch = Branch::factory()->create();
    $space = EventSpace::factory()->create(['branch_id' => $branch->id, 'hourly_rate_cents' => 10000]);
    $booking = app(CreateEventBookingAction::class)->handle($branch, $space, 'Event', 'conference', Carbon::parse('2026-08-01 09:00'), Carbon::parse('2026-08-01 13:00'));

    $catering = EventService::factory()->create(['branch_id' => $branch->id, 'unit_price_cents' => 3000]);
    $equipment = EventService::factory()->create(['branch_id' => $branch->id, 'unit_price_cents' => 15000]);

    app(AddEventBookingItemAction::class)->handle($booking, $catering, 20);
    app(AddEventBookingItemAction::class)->handle($booking, $equipment, 1);

    $bill = app(EventBillCalculator::class)->calculate($booking->fresh());

    // 4 hours * 10000c = 40000c venue; 20*3000 + 1*15000 = 75000c items.
    expect($bill['venue_cents'])->toBe(40000)
        ->and($bill['items_total_cents'])->toBe(75000)
        ->and($bill['total_cents'])->toBe(115000);
});
