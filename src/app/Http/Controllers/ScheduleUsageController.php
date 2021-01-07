<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScheduleMasterService;
use App\Http\Resources\ScheduleUsageResource;
use App\Http\Requests\ScheduleUsageRequest;
use App\Http\Requests\ScheduleUsageReorderRequest;

class ScheduleUsageController extends Controller
{
    protected $scheduleMasterService;

    public function __construct(ScheduleMasterService $scheduleMasterService)
    {
        $this->scheduleMasterService = $scheduleMasterService;
    }

    public function list()
    {
        return ScheduleUsageResource::collection(
            $this->scheduleMasterService->getScheduleUsageList()
        );
    }

    public function get($id)
    {
        return $this->responseWithCode(new ScheduleUsageResource(
            $this->scheduleMasterService->getScheduleUsage($id)
        ));
    }

    public function add(ScheduleUsageRequest $request)
    {
        return $this->success([
            'id' => $this->scheduleMasterService->addScheduleUsage($request->values()),
        ]);
    }

    public function update(ScheduleUsageRequest $request, $id)
    {
        return $this->success([
            'id' => $this->scheduleMasterService->updateScheduleUsage($id, $request->values()),
        ]);
    }

    public function reorder(ScheduleUsageReorderRequest $request)
    {
        $this->scheduleMasterService->reorderScheduleUsage($request->values()['id_list']);
        return $this->success();
    }
}
