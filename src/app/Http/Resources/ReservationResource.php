<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\FormatService;

class ReservationResource extends JsonResource
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
            'schedule_id' => $this->schedule_id,
            'schedule' => new ScheduleResource($this->whenLoaded('schedule')),
            'begins_at' => $formatService->time($this->begins_at),
            'ends_at' => $formatService->time($this->ends_at),
            'reservation_status_id' => $this->reservation_status_id,
            'reservation_status' => new ReservationStatusResource($this->whenLoaded('reservation_status')),
        ];
    }
}
