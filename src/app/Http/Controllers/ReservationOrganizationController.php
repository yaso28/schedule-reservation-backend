<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScheduleMasterService;
use App\Http\Resources\ReservationOrganizationResource;
use App\Http\Requests\ReservationOrganizationRequest;
use App\Http\Requests\ReservationOrganizationReorderRequest;

class ReservationOrganizationController extends Controller
{
    protected $scheduleMasterService;

    public function __construct(ScheduleMasterService $scheduleMasterService)
    {
        $this->scheduleMasterService = $scheduleMasterService;
    }

    public function list()
    {
        return ReservationOrganizationResource::collection(
            $this->scheduleMasterService->getReservationOrganizationList()
        );
    }

    public function get($id)
    {
        return new ReservationOrganizationResource(
            $this->scheduleMasterService->getReservationOrganization($id)
        );
    }

    public function add(ReservationOrganizationRequest $request)
    {
        return $this->success([
            'id' => $this->scheduleMasterService->addReservationOrganization($request->values()),
        ]);
    }

    public function update(ReservationOrganizationRequest $request, $id)
    {
        return $this->success([
            'id' => $this->scheduleMasterService->updateReservationOrganization($id, $request->values()),
        ]);
    }

    public function reorder(ReservationOrganizationReorderRequest $request)
    {
        $this->scheduleMasterService->reorderReservationOrganization($request->values()['id_list']);
        return $this->success();
    }
}
