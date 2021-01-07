<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReservationService;
use App\Http\Resources\ReservationResource;
use App\Http\Requests\ReservationBulkChangeRequest;
use App\Http\Requests\ReservationSplitRequest;
use Illuminate\Support\Arr;

class ReservationController extends Controller
{
    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function list(Request $request)
    {
        return ReservationResource::collection(
            $this->reservationService->getReservationList(
                $request->query('from'),
                $request->query('to')
            )
        );
    }

    public function listForSchedule($scheduleId)
    {
        return ReservationResource::collection(
            $this->reservationService->getReservationListForSchedule($scheduleId)
        );
    }

    public function get($id)
    {
        return $this->responseWithCode(new ReservationResource(
            $this->reservationService->getReservation($id)
        ));
    }

    public function split(ReservationSplitRequest $request, $id)
    {
        $scheduleId = $this->reservationService->splitReservation($id, $request->value());
        return $this->success(['schedule_id' => $scheduleId]);
    }

    public function bulkChange(ReservationBulkChangeRequest $request)
    {
        $values = $request->values();
        $this->reservationService->bulkChangeReservation(
            $values['id_list'],
            Arr::except($values, 'id_list')
        );
        return $this->success();
    }
}
