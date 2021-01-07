<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\FormatService;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $formatService = app(FormatService::class);

        return [
            'id' => $this->id,
            'ymd' => $formatService->date($this->ymd),
            'begins_at' => $formatService->time($this->begins_at),
            'ends_at' => $formatService->time($this->ends_at),
            'schedule_place_id' => $this->schedule_place_id,
            'schedule_place' => new SchedulePlaceResource($this->whenLoaded('schedule_place')),
            'schedule_usage_id' => $this->schedule_usage_id,
            'schedule_usage' => new ScheduleUsageResource($this->whenLoaded('schedule_usage')),
            'schedule_timetable_id' => $this->schedule_timetable_id,
            'schedule_timetable' => new ScheduleTimetableResource($this->whenLoaded('schedule_timetable')),
            'reservation_status_id' => $this->reservation_status_id,
            'reservation_status' => new ReservationStatusResource($this->whenLoaded('reservation_status')),
            'schedule_status_id' => $this->schedule_status_id,
            'schedule_status' => new ScheduleStatusResource($this->whenLoaded('schedule_status')),
        ];
    }
}
