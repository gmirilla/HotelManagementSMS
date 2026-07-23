<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Actions;

use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Reservation\Support\AvailabilityChecker;
use App\Domain\Room\Support\RateResolver;
use App\Models\Reservation;
use App\Models\RoomType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateReservationAction
{
    public function __construct(
        private readonly AvailabilityChecker $availability,
        private readonly RateResolver $rateResolver,
    ) {}

    public function handle(
        int $branchId,
        int $guestId,
        RoomType $roomType,
        Carbon $arrival,
        Carbon $departure,
        int $adults,
        int $children,
        string $source,
        ?int $corporateAccountId = null,
        ?string $specialRequests = null,
    ): Reservation {
        if (! $arrival->lt($departure)) {
            throw ValidationException::withMessages(['departure_date' => __('Departure date must be after the arrival date.')]);
        }

        if (! $this->availability->isAvailable($roomType, $arrival, $departure)) {
            throw ValidationException::withMessages(['room_type_id' => __('No rooms of this type are available for the selected dates.')]);
        }

        $nightlyRates = $this->rateResolver->nightlyRatesForStay($roomType, $arrival, $departure);
        $averageRateCents = (int) round(array_sum($nightlyRates) / max(1, count($nightlyRates)));

        return DB::transaction(function () use (
            $branchId, $guestId, $corporateAccountId, $source, $arrival, $departure,
            $adults, $children, $specialRequests, $roomType, $averageRateCents,
        ) {
            $reservation = Reservation::create([
                'branch_id' => $branchId,
                'guest_id' => $guestId,
                'corporate_account_id' => $corporateAccountId,
                'confirmation_code' => $this->generateConfirmationCode(),
                'source' => $source,
                'status' => ReservationStatus::Confirmed,
                'arrival_date' => $arrival,
                'departure_date' => $departure,
                'adults' => $adults,
                'children' => $children,
                'special_requests' => $specialRequests,
            ]);

            $reservation->rooms()->create([
                'room_type_id' => $roomType->id,
                'room_id' => null,
                'rate_cents' => $averageRateCents,
                'occupants_adults' => $adults,
                'occupants_children' => $children,
            ]);

            $reservation->statusLogs()->create([
                'from_status' => null,
                'to_status' => ReservationStatus::Confirmed->value,
                'reason' => 'Reservation created',
            ]);

            return $reservation;
        });
    }

    private function generateConfirmationCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Reservation::where('confirmation_code', $code)->exists());

        return $code;
    }
}
