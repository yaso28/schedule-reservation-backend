<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReservationService;
use App\Services\MonthScheduleService;
use App\Http\Resources\MonthResource;
use App\Http\Requests\SendRequest;

class MonthController extends Controller
{
    protected $reservationService;
    protected $monthScheduleService;

    public function __construct(
        ReservationService $reservationService,
        MonthScheduleService $monthScheduleService
    ) {
        $this->reservationService = $reservationService;
        $this->monthScheduleService = $monthScheduleService;
    }

    public function list(Request $request)
    {
        return MonthResource::collection(
            $this->reservationService->getMonthList(
                $request->query('year_from'),
                $request->query('month_from')
            )
        );
    }

    public function get($id)
    {
        return $this->responseWithCode(new MonthResource(
            $this->reservationService->getMonth($id)
        ));
    }

    public function prepareSendInfo($id)
    {
        $sendInfo = $this->monthScheduleService->prepareSendInfo($id);
        $sendInfo['month'] = new MonthResource($sendInfo['month']);
        return $this->success($sendInfo);
    }

    public function send(SendRequest $request, $id)
    {
        return $this->success([
            'id' => $this->monthScheduleService->send($id, $request->values()),
        ]);
    }
}
