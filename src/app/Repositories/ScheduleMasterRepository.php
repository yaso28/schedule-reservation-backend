<?php

namespace App\Repositories;

use App\Models\ScheduleStatus;
use App\Models\ReservationStatus;
use App\Models\ReservationOrganization;
use App\Models\SchedulePlace;
use App\Models\ScheduleUsage;
use App\Models\ScheduleTimetable;

class ScheduleMasterRepository extends RepositoryBase
{
    public function selectAllScheduleStatus()
    {
        return $this->selectAllMaster(ScheduleStatus::class);
    }

    public function selectInitialScheduleStatus()
    {
        return ScheduleStatus::orderBy('order_reverse', 'desc')->orderBy('id')->firstOrFail();
    }

    public function selectFixedScheduleStatus()
    {
        return ScheduleStatus::where('bulk_change_mode', ScheduleStatus::BULK_CHANGE_TO)->orderBy('order_reverse', 'desc')->orderBy('id')->firstOrFail();
    }

    public function selectAllReservationStatus()
    {
        return $this->selectAllMaster(ReservationStatus::class);
    }

    public function selectInitialReservationStatus()
    {
        return ReservationStatus::orderBy('order_reverse', 'desc')->orderBy('id')->firstOrFail();
    }

    public function selectAllReservationOrganization()
    {
        return $this->selectAllMaster(ReservationOrganization::class);
    }

    public function selectReservationOrganization($id)
    {
        return $this->select(ReservationOrganization::class, $id);
    }

    public function insertReservationOrganization($attributes)
    {
        return $this->insert(ReservationOrganization::class, $attributes);
    }

    public function updateReservationOrganization($id, $attributes)
    {
        return $this->update(ReservationOrganization::class, $id, $attributes);
    }

    public function reorderReservationOrganization($dataList)
    {
        $this->reorderMaster(ReservationOrganization::class, $dataList);
    }

    public function selectAllSchedulePlace()
    {
        return $this->selectAllMaster(SchedulePlace::class);
    }

    public function selectSchedulePlace($id)
    {
        return $this->select(SchedulePlace::class, $id);
    }

    public function insertSchedulePlace($attributes)
    {
        return $this->insert(SchedulePlace::class, $attributes);
    }

    public function updateSchedulePlace($id, $attributes)
    {
        return $this->update(SchedulePlace::class, $id, $attributes);
    }

    public function reorderSchedulePlace($dataList)
    {
        $this->reorderMaster(SchedulePlace::class, $dataList);
    }

    public function selectAllScheduleUsage()
    {
        return $this->selectAllMaster(ScheduleUsage::class, 'reservation_organization');
    }

    public function selectScheduleUsage($id)
    {
        return $this->select(ScheduleUsage::class, $id, 'reservation_organization');
    }

    public function insertScheduleUsage($attributes)
    {
        return $this->insert(ScheduleUsage::class, $attributes);
    }

    public function updateScheduleUsage($id, $attributes)
    {
        return $this->update(ScheduleUsage::class, $id, $attributes);
    }

    public function reorderScheduleUsage($dataList)
    {
        $this->reorderMaster(ScheduleUsage::class, $dataList);
    }

    public function selectAllScheduleTimetable()
    {
        return $this->selectAllMaster(ScheduleTimetable::class);
    }

    public function selectScheduleTimetable($id)
    {
        return $this->select(ScheduleTimetable::class, $id);
    }

    public function insertScheduleTimetable($attributes)
    {
        return $this->insert(ScheduleTimetable::class, $attributes);
    }

    public function updateScheduleTimetable($id, $attributes)
    {
        return $this->update(ScheduleTimetable::class, $id, $attributes);
    }

    public function reorderScheduleTimetable($dataList)
    {
        $this->reorderMaster(ScheduleTimetable::class, $dataList);
    }
}
