<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScheduleMasterService;
use App\Http\Resources\ScheduleStatusResource;

class ScheduleStatusController extends Controller
{
    protected $scheduleMasterService;

    public function __construct(ScheduleMasterService $scheduleMasterService)
    {
        $this->scheduleMasterService = $scheduleMasterService;
    }

    public function list()
    {
        return ScheduleStatusResource::collection(
            $this->scheduleMasterService->getScheduleStatusList()
        );
    }
}
