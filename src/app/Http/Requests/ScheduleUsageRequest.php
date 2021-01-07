<?php

namespace App\Http\Requests;

class ScheduleUsageRequest extends RequestBase
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $model = 'App\Models\ScheduleUsage';

        return [
            'name' => "bail|required|{$this->getUniqueRule($model, 'name')}",
            'is_public' => "bail|required|boolean",
            'reservation_organization_id' => "bail|required|exists:App\Models\ReservationOrganization,id",
        ];
    }

    public function values()
    {
        return $this->only([
            'name',
            'is_public',
            'reservation_organization_id',
        ]);
    }
}
