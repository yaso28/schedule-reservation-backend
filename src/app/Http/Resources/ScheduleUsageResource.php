<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\FormatService;

class ScheduleUsageResource extends JsonResource
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
            'name' => $this->name,
            'is_public' => $formatService->bool($this->is_public),
            'reservation_organization_id' => $this->reservation_organization_id,
            'reservation_organization' => new ReservationOrganizationResource($this->whenLoaded('reservation_organization')),
        ];
    }
}
