<?php

declare(strict_types=1);

namespace App\Domain\Guest\Actions;

use App\Domain\Guest\Enums\DocumentType;
use App\Domain\Guest\Enums\GuestFlag;
use App\Domain\Guest\Enums\GuestType;
use App\Models\Guest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateGuestAction
{
    public function handle(
        int $tenantId,
        string $firstName,
        string $lastName,
        ?string $email = null,
        ?string $phone = null,
        ?string $nationality = null,
        GuestType $guestType = GuestType::Individual,
        GuestFlag $flag = GuestFlag::None,
        ?DocumentType $documentType = null,
        ?string $documentNumber = null,
        ?string $documentIssuingCountry = null,
        ?Carbon $documentExpiresOn = null,
    ): Guest {
        return DB::transaction(function () use (
            $tenantId, $firstName, $lastName, $email, $phone, $nationality, $guestType, $flag,
            $documentType, $documentNumber, $documentIssuingCountry, $documentExpiresOn,
        ) {
            $guest = Guest::create([
                'tenant_id' => $tenantId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'nationality' => $nationality,
                'guest_type' => $guestType,
                'flag' => $flag,
            ]);

            if ($documentType !== null && $documentNumber !== null) {
                $guest->documents()->create([
                    'document_type' => $documentType,
                    'document_number' => $documentNumber,
                    'issuing_country' => $documentIssuingCountry,
                    'expires_on' => $documentExpiresOn,
                ]);
            }

            return $guest;
        });
    }
}
