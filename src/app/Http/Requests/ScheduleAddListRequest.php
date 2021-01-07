<?php

namespace App\Http\Requests;

use App\Services\FormatService;

class ScheduleAddListRequest extends RequestBase
{
    public function rules()
    {
        $dateFormat = FormatService::DATE_FORMAT;
        $timeFormat = FormatService::TIME_FORMAT;
        return [
            'ymd_list' => "bail|required|array",
            'ymd_list.*' => "bail|required|date_format:{$dateFormat}",
            'begins_at' => "bail|required|date_format:{$timeFormat}",
            'ends_at' => "bail|required|date_format:{$timeFormat}",
            'schedule_place_id' => "bail|required|exists:App\Models\SchedulePlace,id",
            'schedule_usage_id' => "bail|required|exists:App\Models\ScheduleUsage,id",
            'schedule_timetable_id' => "bail|nullable|exists:App\Models\ScheduleTimetable,id",
            'reservation_status_id' => "bail|required|exists:App\Models\ReservationStatus,id",
            'schedule_status_id' => "bail|required|exists:App\Models\ScheduleStatus,id",
        ];
    }

    public function values()
    {
        return $this->only([
            'ymd_list',
            'begins_at',
            'ends_at',
            'schedule_place_id',
            'schedule_usage_id',
            'schedule_timetable_id',
            'reservation_status_id',
            'schedule_status_id',
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $keyTimeList = ['begins_at', 'ends_at'];
            if (!$validator->errors()->hasAny($keyTimeList)) {
                if ($this->input($keyTimeList[0]) >= $this->input($keyTimeList[1])) {
                    foreach ($keyTimeList as $key) {
                        $validator->errors()->add($key, __('validation.time_order'));
                    }
                }
            }
        });
    }
}
