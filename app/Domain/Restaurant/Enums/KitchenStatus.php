<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Enums;

enum KitchenStatus: string
{
    case Queued = 'queued';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';

    /**
     * Forward-only: Queued -> Preparing -> Ready -> Served, one step at a
     * time, never backward or skipping a step. Re-requesting the current
     * status is allowed as a harmless no-op (e.g. a double-clicked "advance"
     * once an item is already Served) rather than an error.
     */
    public function canAdvanceTo(self $next): bool
    {
        if ($next === $this) {
            return true;
        }

        return match ($this) {
            self::Queued => $next === self::Preparing,
            self::Preparing => $next === self::Ready,
            self::Ready => $next === self::Served,
            self::Served => false,
        };
    }
}
