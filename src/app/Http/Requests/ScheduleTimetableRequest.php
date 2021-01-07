<?php

namespace App\Http\Requests;

class ScheduleTimetableRequest extends RequestBase
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $model = 'App\Models\ScheduleTimetable';

        return [
            'name' => "bail|required|{$this->getUniqueRule($model, 'name')}",
            'details' => "bail|required",
        ];
    }

    public function values()
    {
        return $this->only([
            'name',
            'details',
        ]);
    }
}
