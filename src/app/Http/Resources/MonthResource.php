<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MonthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'month' => $this->month,
            'reservation_status_id' => $this->reservation_status_id,
            'reservation_status' => new ReservationStatusResource($this->whenLoaded('reservation_status')),
            'schedule_status_id' => $this->schedule_status_id,
            'schedule_status' => new ScheduleStatusResource($this->whenLoaded('schedule_status')),
            'name' => $this->name,
            'first_day' => $this->first_day,
            'last_day' => $this->last_day,
        ];
    }
}
