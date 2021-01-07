<?php

namespace App\Http\Requests;

class ScheduleBulkChangeRequest extends RequestBase
{
    public function rules()
    {
        return [
            'id_list' => "bail|required|array",
            'id_list.*' => "bail|exists:App\Models\Schedule,id",
            'schedule_status_id' => "bail|required|exists:App\Models\ScheduleStatus,id",
        ];
    }

    public function values()
    {
        return $this->only([
            'id_list',
            'schedule_status_id',
        ]);
    }
}
