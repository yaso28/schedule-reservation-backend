<?php

namespace App\Services;

use App\Repositories\ScheduleMasterRepository;

class ScheduleMasterService
{
    protected $scheduleMasterRepository;

    public function __construct(ScheduleMasterRepository $scheduleMasterRepository)
    {
        $this->scheduleMasterRepository = $scheduleMasterRepository;
    }

    protected function makeOrderReverse($idList)
    {
        $idCount = count($idList);
        $result = [];
        foreach ($idList as $index => $id) {
            $result[] = [
                'id' => $id,
                'order_reverse' => $idCount - $index,
            ];
        }
        return $result;
    }

    public function getScheduleStatusList()
    {
        return $this->scheduleMasterRepository->selectAllScheduleStatus();
    }

    protected $initialScheduleStatus;
    public function getInitialScheduleStatus()
    {
        if (!$this->initialScheduleStatus) {
            $this->initialScheduleStatus = $this->scheduleMasterRepository->selectInitialScheduleStatus();
        }
        return $this->initialScheduleStatus;
    }

    protected $fixedScheduleStatus;
    public function getFixedScheduleStatus()
    {
        if (!$this->fixedScheduleStatus) {
            $this->fixedScheduleStatus = $this->scheduleMasterRepository->selectFixedScheduleStatus();
        }
        return $this->fixedScheduleStatus;
    }

    public function getReservationStatusList()
    {
        return $this->scheduleMasterRepository->selectAllReservationStatus();
    }

    protected $initialReservationStatus;
    public function getInitialReservationStatus()
    {
        if (!$this->initialReservationStatus) {
            $this->initialReservationStatus = $this->scheduleMasterRepository->selectInitialReservationStatus();
        }
        return $this->initialReservationStatus;
    }

    public function getReservationOrganizationList()
    {
        return $this->scheduleMasterRepository->selectAllReservationOrganization();
    }

    public function getReservationOrganization($id)
    {
        return $this->scheduleMasterRepository->selectReservationOrganization($id);
    }

    public function addReservationOrganization($attributes)
    {
        return $this->scheduleMasterRepository->insertReservationOrganization($attributes);
    }

    public function updateReservationOrganization($id, $attributes)
    {
        return $this->scheduleMasterRepository->updateReservationOrganization($id, $attributes);
    }

    public function reorderReservationOrganization($idList)
    {
        $this->scheduleMasterRepository->reorderReservationOrganization($this->makeOrderReverse($idList));
    }

    public function getSchedulePlaceList()
    {
        return $this->scheduleMasterRepository->selectAllSchedulePlace();
    }

    public function getSchedulePlace($id)
    {
        return $this->scheduleMasterRepository->selectSchedulePlace($id);
    }

    public function addSchedulePlace($attributes)
    {
        return $this->scheduleMasterRepository->insertSchedulePlace($attributes);
    }

    public function updateSchedulePlace($id, $attributes)
    {
        return $this->scheduleMasterRepository->updateSchedulePlace($id, $attributes);
    }

    public function reorderSchedulePlace($idList)
    {
        $this->scheduleMasterRepository->reorderSchedulePlace($this->makeOrderReverse($idList));
    }

    public function getScheduleUsageList()
    {
        return $this->scheduleMasterRepository->selectAllScheduleUsage();
    }

    public function getScheduleUsage($id)
    {
        return $this->scheduleMasterRepository->selectScheduleUsage($id);
    }

    public function addScheduleUsage($attributes)
    {
        return $this->scheduleMasterRepository->insertScheduleUsage($attributes);
    }

    public function updateScheduleUsage($id, $attributes)
    {
        return $this->scheduleMasterRepository->updateScheduleUsage($id, $attributes);
    }

    public function reorderScheduleUsage($idList)
    {
        $this->scheduleMasterRepository->reorderScheduleUsage($this->makeOrderReverse($idList));
    }

    public function getScheduleTimetableList()
    {
        return $this->scheduleMasterRepository->selectAllScheduleTimetable();
    }

    public function getScheduleTimetable($id)
    {
        return $this->scheduleMasterRepository->selectScheduleTimetable($id);
    }

    public function addScheduleTimetable($attributes)
    {
        return $this->scheduleMasterRepository->insertScheduleTimetable($attributes);
    }

    public function updateScheduleTimetable($id, $attributes)
    {
        return $this->scheduleMasterRepository->updateScheduleTimetable($id, $attributes);
    }

    public function reorderScheduleTimetable($idList)
    {
        $this->scheduleMasterRepository->reorderScheduleTimetable($this->makeOrderReverse($idList));
    }
}
