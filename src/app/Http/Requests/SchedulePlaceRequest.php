<?php

namespace App\Http\Requests;

class SchedulePlaceRequest extends RequestBase
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $model = 'App\Models\SchedulePlace';

        return [
            'name' => "bail|required|{$this->getUniqueRule($model, 'name')}",
            'abbreviation' => "bail|required|{$this->getUniqueRule($model, 'abbreviation')}",
            'price_per_hour' => "bail|required|integer|min:0",
        ];
    }

    public function values()
    {
        return $this->only([
            'name',
            'abbreviation',
            'price_per_hour',
        ]);
    }
}
