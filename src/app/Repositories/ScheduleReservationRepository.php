<?php

namespace App\Repositories;

use App\Models\Month;
use App\Models\Schedule;
use App\Models\Reservation;

class ScheduleReservationRepository extends RepositoryBase
{
    protected $monthRelations = ['reservation_status', 'schedule_status'];

    public function selectMonthList($yearFrom, $monthFrom)
    {
        return Month::where('year', '>', $yearFrom)
            ->orWhere(fn ($query) => $query->where('year', $yearFrom)->where('month', '>=', $monthFrom))
            ->with($this->monthRelations)
            ->orderBy('year')->orderBy('month')
            ->get();
    }

    public function selectMonth($id)
    {
        return $this->select(
            Month::class,
            $id,
            $this->monthRelations
        );
    }

    public function selectMonthViaYm($year, $month)
    {
        return Month::where('year', $year)
            ->where('month', $month)
            ->with($this->monthRelations)
            ->first();
    }

    public function selectScheduleList($isPublicOnly, $ymdFrom, $ymdTo = null)
    {
        $query = Schedule::where('ymd', '>=', $ymdFrom);
        if ($ymdTo) {
            $query = $query->where('ymd', '<=', $ymdTo);
        }
        $relations = [
            'schedule_place',
            'schedule_usage',
            'schedule_timetable',
            'schedule_status'
        ];
        if ($isPublicOnly) {
            $query = $query->whereHas('schedule_status', fn ($q) => $q->where('is_public', true))
                ->whereHas('schedule_usage', fn ($q) => $q->where('is_public', true));
        } else {
            array_push(
                $relations,
                'reservation_status',
                'schedule_usage.reservation_organization'
            );
        }
        return $query->with($relations)
            ->orderBy('schedules.ymd')
            ->orderBy('schedules.begins_at')
            ->orderBy('schedules.ends_at')
            ->join('schedule_usages', 'schedule_usage_id', '=', 'schedule_usages.id')
            ->select('schedules.*')
            ->orderBy('schedule_usages.order_reverse', 'desc')
            ->orderBy('schedule_usages.id')
            ->orderBy('schedules.id')
            ->get();
    }

    public function selectScheduleIdListViaReservationIdList($reservationIdList)
    {
        $columnScheduleId = 'schedule_id';
        return Reservation::whereIn('id', $reservationIdList)
            ->select($columnScheduleId)->distinct()->orderBy($columnScheduleId)->get()
            ->map(fn ($record) => $record[$columnScheduleId]);
    }

    public function selectScheduleYmdListViaScheduleIdList($scheduleIdList)
    {
        return Schedule::whereIn('id', $scheduleIdList)
            ->select('ymd')->distinct()->orderBy('ymd')->get()
            ->map(fn ($record) => $record->ymd);
    }

    public function selectSchedule($id)
    {
        return $this->select(
            Schedule::class,
            $id,
            [
                'schedule_place',
                'schedule_usage',
                'schedule_timetable',
                'schedule_status',
                'reservation_status',
                'schedule_usage.reservation_organization'
            ]
        );
    }

    public function insertSchedule($attributes)
    {
        return $this->insert(Schedule::class, $attributes);
    }

    public function updateSchedule($id, $attributes)
    {
        return $this->update(Schedule::class, $id, $attributes);
    }

    public function bulkUpdateSchedule($idList, $attributes)
    {
        Schedule::whereIn('id', $idList)->update($attributes);
    }

    public function selectReservationList($ymdFrom, $ymdTo = null)
    {
        $query = Reservation::with([
            'schedule',
            'schedule.schedule_place',
            'schedule.schedule_usage',
            'schedule.schedule_usage.reservation_organization',
            'reservation_status'
        ])
            ->join('schedules', 'reservations.schedule_id', '=', 'schedules.id')
            ->join('schedule_usages', 'schedule_usage_id', '=', 'schedule_usages.id')
            ->select('reservations.*')
            ->orderBy('schedules.ymd')
            ->orderBy('schedules.begins_at')
            ->orderBy('schedules.ends_at')
            ->orderBy('schedule_usages.order_reverse', 'desc')
            ->orderBy('schedule_usages.id')
            ->orderBy('schedules.id')
            ->orderBy('reservations.begins_at')
            ->orderBy('reservations.ends_at')
            ->orderBy('reservations.id');
        $query = $query->where('schedules.ymd', '>=', $ymdFrom);
        if ($ymdTo) {
            $query = $query->where('schedules.ymd', '<=', $ymdTo);
        }
        return $query->get();
    }

    public function selectReservationListViaScheduleId($scheduleId)
    {
        return Reservation::with('reservation_status')
            ->where('schedule_id', $scheduleId)
            ->orderBy('begins_at')
            ->orderBy('ends_at')
            ->orderBy('id')
            ->get();
    }

    public function selectReservation($id)
    {
        return $this->select(
            Reservation::class,
            $id,
            [
                'schedule',
                'schedule.schedule_place',
                'schedule.schedule_usage',
                'schedule.schedule_usage.reservation_organization',
                'reservation_status'
            ]
        );
    }

    public function insertReservation($attributes)
    {
        return $this->insert(Reservation::class, $attributes);
    }

    public function updateReservation($id, $attributes)
    {
        return $this->update(Reservation::class, $id, $attributes);
    }

    public function bulkUpdateReservation($idList, $attributes)
    {
        Reservation::whereIn('id', $idList)->update($attributes);
    }
}
