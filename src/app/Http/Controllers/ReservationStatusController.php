<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScheduleMasterService;
use App\Http\Resources\ReservationStatusResource;

class ReservationStatusController extends Controller
{
    protected $scheduleMasterService;

    public function __construct(ScheduleMasterService $scheduleMasterService)
    {
        $this->scheduleMasterService = $scheduleMasterService;
    }

    public function list()
    {
        return ReservationStatusResource::collection(
            $this->scheduleMasterService->getReservationStatusList()
        );
    }
}
