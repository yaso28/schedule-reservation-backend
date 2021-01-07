<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReservationBulkChangeRequest extends RequestBase
{
    public function rules()
    {
        return [
            'id_list' => "bail|required|array",
            'id_list.*' => "bail|exists:App\Models\Reservation,id",
            'reservation_status_id' => "bail|required|exists:App\Models\ReservationStatus,id",
        ];
    }

    public function values()
    {
        return $this->only([
            'id_list',
            'reservation_status_id',
        ]);
    }
}
