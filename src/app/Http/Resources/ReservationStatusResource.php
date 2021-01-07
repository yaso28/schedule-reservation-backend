<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\FormatService;

class ReservationStatusResource extends JsonResource
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
            'description' => $this->description,
            'reserved' => $formatService->bool($this->reserved),
        ];
    }
}
