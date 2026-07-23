<?php

declare(strict_types=1);

use App\Domain\Room\Support\RateResolver;
use App\Models\RoomRate;
use App\Models\RoomType;
use Illuminate\Support\Carbon;

test('a room type with only a base rate returns that rate every day', function (): void {
    $roomType = RoomType::factory()->create(['base_rate_cents' => 10000]);

    $resolver = app(RateResolver::class);
    $rate = $resolver->nightlyRateCents($roomType, Carbon::parse('2026-03-10'));

    expect($rate)->toBe(10000);
});

test('a weekend rate overrides the base rate on matching days', function (): void {
    $roomType = RoomType::factory()->create(['base_rate_cents' => 10000]);

    RoomRate::factory()->create([
        'room_type_id' => $roomType->id,
        'rate_type' => 'weekend',
        'days_of_week' => [5, 6],
        'rate_cents' => 15000,
        'priority' => 10,
    ]);

    $roomType->load('rates');
    $resolver = app(RateResolver::class);

    // 2026-03-14 is a Saturday.
    expect($resolver->nightlyRateCents($roomType, Carbon::parse('2026-03-14')))->toBe(15000)
        // 2026-03-10 is a Tuesday.
        ->and($resolver->nightlyRateCents($roomType, Carbon::parse('2026-03-10')))->toBe(10000);
});

test('a seasonal override wins over a lower-priority weekend rate on the same day', function (): void {
    $roomType = RoomType::factory()->create(['base_rate_cents' => 10000]);

    RoomRate::factory()->create([
        'room_type_id' => $roomType->id,
        'rate_type' => 'weekend',
        'days_of_week' => [5, 6],
        'rate_cents' => 15000,
        'priority' => 10,
    ]);

    RoomRate::factory()->create([
        'room_type_id' => $roomType->id,
        'rate_type' => 'seasonal',
        'starts_on' => '2026-03-01',
        'ends_on' => '2026-03-31',
        'rate_cents' => 25000,
        'priority' => 20,
    ]);

    $roomType->load('rates');
    $resolver = app(RateResolver::class);

    // Saturday, March 14 — both the weekend and seasonal rate apply; seasonal has higher priority.
    expect($resolver->nightlyRateCents($roomType, Carbon::parse('2026-03-14')))->toBe(25000);
});

test('nightlyRatesForStay returns one entry per night, excluding the departure date', function (): void {
    $roomType = RoomType::factory()->create(['base_rate_cents' => 10000]);
    $roomType->load('rates');

    $resolver = app(RateResolver::class);
    $rates = $resolver->nightlyRatesForStay($roomType, Carbon::parse('2026-03-10'), Carbon::parse('2026-03-13'));

    expect($rates)->toHaveCount(3)
        ->and(array_keys($rates))->toBe(['2026-03-10', '2026-03-11', '2026-03-12']);
});
