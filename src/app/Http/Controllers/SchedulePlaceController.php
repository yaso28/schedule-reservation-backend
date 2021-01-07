<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScheduleMasterService;
use App\Http\Resources\SchedulePlaceResource;
use App\Http\Requests\SchedulePlaceRequest;
use App\Http\Requests\SchedulePlaceReorderRequest;

class SchedulePlaceController extends Controller
{
    protected $scheduleMasterService;

    public function __construct(ScheduleMasterService $scheduleMasterService)
    {
        $this->scheduleMasterService = $scheduleMasterService;
    }

    public function list()
    {
        return SchedulePlaceResource::collection(
            $this->scheduleMasterService->getSchedulePlaceList()
        );
    }

    public function get($id)
    {
        return new SchedulePlaceResource(
            $this->scheduleMasterService->getSchedulePlace($id)
        );
    }

    public function add(SchedulePlaceRequest $request)
    {
        return $this->success([
            'id' => $this->scheduleMasterService->addSchedulePlace($request->values()),
        ]);
    }

    public function update(SchedulePlaceRequest $request, $id)
    {
        return $this->success([
            'id' => $this->scheduleMasterService->updateSchedulePlace($id, $request->values()),
        ]);
    }

    public function reorder(SchedulePlaceReorderRequest $request)
    {
        $this->scheduleMasterService->reorderSchedulePlace($request->values()['id_list']);
        return $this->success();
    }
}
