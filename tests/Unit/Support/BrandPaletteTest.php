<?php

declare(strict_types=1);

use App\Support\Theme\BrandPalette;

test('isValidHex accepts six-digit hex colors and rejects everything else', function (): void {
    expect(BrandPalette::isValidHex('#4f46e5'))->toBeTrue()
        ->and(BrandPalette::isValidHex('#FFFFFF'))->toBeTrue()
        ->and(BrandPalette::isValidHex('4f46e5'))->toBeFalse()
        ->and(BrandPalette::isValidHex('#fff'))->toBeFalse()
        ->and(BrandPalette::isValidHex('not-a-color'))->toBeFalse()
        ->and(BrandPalette::isValidHex('#gggggg'))->toBeFalse();
});

test('ramp reproduces every preset exactly at shade 600, the stop primary buttons use', function (): void {
    foreach (BrandPalette::PRESETS as $hex) {
        $ramp = BrandPalette::ramp($hex);

        expect($ramp[600])->toBe($hex);
    }
});

test('ramp produces all ten shades in ascending darkness order', function (): void {
    $ramp = BrandPalette::ramp('#4f46e5');

    expect(array_keys($ramp))->toBe([50, 100, 200, 300, 400, 500, 600, 700, 800, 900]);

    $lightnesses = array_values(array_map(
        fn (string $hex): int => hexdec(substr($hex, 1, 2)) + hexdec(substr($hex, 3, 2)) + hexdec(substr($hex, 5, 2)),
        $ramp,
    ));
    $counter = count($lightnesses);

    // Each successive shade should be at least as dark as the one before —
    // the whole point of a shade ramp is a monotonic light-to-dark curve.
    for ($i = 1; $i < $counter; $i++) {
        expect($lightnesses[$i])->toBeLessThanOrEqual($lightnesses[$i - 1]);
    }
});

test('ramp falls back to the default color for an invalid or missing hex', function (): void {
    expect(BrandPalette::ramp(null)[600])->toBe(BrandPalette::DEFAULT_COLOR)
        ->and(BrandPalette::ramp('nonsense')[600])->toBe(BrandPalette::DEFAULT_COLOR);
});

test('ramp stays well-formed for near-white and near-black picks', function (): void {
    foreach (['#ffffff', '#fdf2f8', '#000000', '#0a0a0a'] as $extreme) {
        $ramp = BrandPalette::ramp($extreme);

        expect($ramp)->toHaveCount(10);

        foreach ($ramp as $hex) {
            expect($hex)->toMatch('/^#[0-9a-f]{6}$/');
        }
    }
});

test('cssVariables renders a :root block with all ten custom properties', function (): void {
    $css = BrandPalette::cssVariables('#059669');

    expect($css)->toStartWith(':root{')
        ->and($css)->toContain('--color-brand-50:')
        ->and($css)->toContain('--color-brand-600: #059669;')
        ->and($css)->toContain('--color-brand-900:');
});
