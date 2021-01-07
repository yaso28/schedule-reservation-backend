<?php

namespace App\Http\Requests;

class ReservationOrganizationRequest extends RequestBase
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $model = 'App\Models\ReservationOrganization';

        return [
            'name' => "bail|required|{$this->getUniqueRule($model, 'name')}",
            'abbreviation' => "bail|required|{$this->getUniqueRule($model, 'abbreviation')}",
            'registration_number' => "bail|required|{$this->getUniqueRule($model, 'registration_number')}",
        ];
    }

    public function values()
    {
        return $this->only([
            'name',
            'abbreviation',
            'registration_number',
        ]);
    }
}
