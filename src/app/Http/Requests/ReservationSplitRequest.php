<?php

namespace App\Http\Requests;

use App\Services\FormatService;

class ReservationSplitRequest extends RequestBase
{
    public function rules()
    {
        $timeFormat = FormatService::TIME_FORMAT;
        return [
            'splits_at' => "bail|required|date_format:{$timeFormat}",
        ];
    }

    public function value()
    {
        return $this->input('splits_at');
    }
}
