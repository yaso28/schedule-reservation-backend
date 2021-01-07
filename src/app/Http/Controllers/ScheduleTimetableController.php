<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScheduleMasterService;
use App\Http\Resources\ScheduleTimetableResource;
use App\Http\Requests\ScheduleTimetableRequest;
use App\Http\Requests\ScheduleTimetableReorderRequest;

class ScheduleTimetableController extends Controller
{
    protected $scheduleMasterService;

    public function __construct(ScheduleMasterService $scheduleMasterService)
    {
        $this->scheduleMasterService = $scheduleMasterService;
    }

    public function list()
    {
        return ScheduleTimetableResource::collection(
            $this->scheduleMasterService->getScheduleTimetableList()
        );
    }

    public function get($id)
    {
        return new ScheduleTimetableResource(
            $this->scheduleMasterService->getScheduleTimetable($id)
        );
    }

    public function add(ScheduleTimetableRequest $request)
    {
        return $this->success([
            'id' => $this->scheduleMasterService->addScheduleTimetable($request->values()),
        ]);
    }

    public function update(ScheduleTimetableRequest $request, $id)
    {
        return $this->success([
            'id' => $this->scheduleMasterService->updateScheduleTimetable($id, $request->values()),
        ]);
    }

    public function reorder(ScheduleTimetableReorderRequest $request)
    {
        $this->scheduleMasterService->reorderScheduleTimetable($request->values()['id_list']);
        return $this->success();
    }
}
