<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\FormatService;

class ScheduleStatusResource extends JsonResource
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
            'display_type' => $this->display_type,
            'is_public' => $formatService->bool($this->is_public),
            'bulk_change_mode' => $this->bulk_change_mode,
        ];
    }
}
