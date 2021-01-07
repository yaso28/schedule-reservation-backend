<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReservationService;
use App\Http\Resources\ScheduleResource;
use App\Http\Requests\ScheduleAddListRequest;
use App\Http\Requests\ScheduleUpdateRequest;
use App\Http\Requests\ScheduleBulkChangeRequest;
use Illuminate\Support\Arr;

class ScheduleController extends Controller
{
    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function list(Request $request)
    {
        return ScheduleResource::collection(
            $this->reservationService->getScheduleList(
                false,
                $request->query('from'),
                $request->query('to')
            )
        );
    }

    public function listPublic()
    {
        return ScheduleResource::collection(
            $this->reservationService->getScheduleList(true)
        );
    }

    public function get($id)
    {
        return $this->responseWithCode(new ScheduleResource(
            $this->reservationService->getSchedule($id)
        ));
    }

    public function addList(ScheduleAddListRequest $request)
    {
        $this->reservationService->addScheduleList($request->values());
        return $this->success();
    }

    public function update(ScheduleUpdateRequest $request, $id)
    {
        return $this->success([
            'id' => $this->reservationService->updateSchedule($id, $request->values()),
        ]);
    }

    public function bulkChange(ScheduleBulkChangeRequest $request)
    {
        $values = $request->values();
        $this->reservationService->bulkChangeSchedule(
            $values['id_list'],
            Arr::except($values, 'id_list')
        );
        return $this->success();
    }
}
