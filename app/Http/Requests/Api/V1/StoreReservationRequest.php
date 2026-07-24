<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Reservation::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'arrival_date' => ['required', 'date', 'after_or_equal:today'],
            'departure_date' => ['required', 'date', 'after:arrival_date'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['sometimes', 'integer', 'min:0'],
            'source' => ['sometimes', 'string', 'in:walk_in,phone,online,corporate,group,ota'],
            'corporate_account_id' => ['nullable', 'integer', 'exists:corporate_accounts,id'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
