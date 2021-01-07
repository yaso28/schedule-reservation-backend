<?php

namespace App\Services;

use App\Repositories\ScheduleReservationRepository;
use App\Models\Month;
use Illuminate\Support\Carbon;

class AdjustReservationStatusService
{
    protected $scheduleReservationRepository;
    protected $scheduleMasterService;

    public function __construct(
        ScheduleReservationRepository $scheduleReservationRepository,
        ScheduleMasterService $scheduleMasterService
    ) {
        $this->scheduleReservationRepository = $scheduleReservationRepository;
        $this->scheduleMasterService = $scheduleMasterService;
    }

    public function adjustScheduleViaScheduleIdList($scheduleIdList)
    {
        foreach ($scheduleIdList as $scheduleId) {
            $this->adjustScheduleViaScheduleId($scheduleId);
        }
    }

    public function adjustScheduleViaScheduleId($scheduleId)
    {
        $schedule = $this->scheduleReservationRepository->selectSchedule($scheduleId);
        $newStatus = $this->getAdjustedReservationStatusForSchedule($schedule);
        $schedule->reservation_status_id = $newStatus->id;
        $this->scheduleReservationRepository->saveEntity($schedule);
    }

    public function getAdjustedReservationStatusForSchedule($schedule)
    {
        $reservationList = $this->scheduleReservationRepository->selectReservationListViaScheduleId($schedule->id);
        $newStatus = null;
        $checkTime = $schedule->begins_at;
        foreach ($reservationList as $reservation) {
            if ($checkTime === $reservation->begins_at) {
                $checkTime = $reservation->ends_at;
                if (
                    !$newStatus ||
                    $newStatus->order_reverse < $reservation->reservation_status->order_reverse ||
                    ($newStatus->order_reverse === $reservation->reservation_status->order_reverse && $newStatus->id > $reservation->reservation_status->id)
                ) {
                    $newStatus = $reservation->reservation_status;
                }
            } else {
                $newStatus = null;
                break;
            }
        }
        if ($checkTime !== $schedule->ends_at) {
            $newStatus = null;
        }

        if (!$newStatus) {
            $newStatus = $this->scheduleMasterService->getInitialReservationStatus();
        }
        return $newStatus;
    }

    public function adjustMonthViaYmdList($ymdList)
    {
        $ymList = collect($ymdList)->map(fn ($ymd) => Carbon::create($ymd))
            ->map(fn ($date) => ['year' => $date->year, 'month' => $date->month])
            ->unique()->sort()->values();
        $this->adjustMonthViaYmList($ymList);
    }

    public function adjustMonthViaYmList($ymList)
    {
        foreach ($ymList as $ym) {
            $this->adjustMonthViaYm($ym);
        }
    }

    public function adjustMonthViaYm($ym)
    {
        $argYear = $ym['year'];
        $argMonth = $ym['month'];
        $month = $this->scheduleReservationRepository->selectMonthViaYm($argYear, $argMonth);
        if (!$month) {
            $month = new Month;
            $month->year = $argYear;
            $month->month = $argMonth;
        }
        $scheduleList = $this->scheduleReservationRepository->selectScheduleList(false, $month->first_day, $month->last_day);
        $month->reservation_status_id =
            ($this->getMinimumStatusOfList(
                $scheduleList,
                fn ($schedule) => $schedule->reservation_status
            ) ?? $this->scheduleMasterService->getInitialReservationStatus())->id;
        $month->schedule_status_id =
            ($this->getMinimumStatusOfList(
                $scheduleList,
                fn ($schedule) => $schedule->schedule_status
            ) ?? $this->scheduleMasterService->getInitialScheduleStatus())->id;
        $this->scheduleReservationRepository->saveEntity($month);
    }

    protected function getMinimumStatusOfList($recordList, $getStatusMapper)
    {
        return $recordList->map($getStatusMapper)->unique()
            ->sort(fn ($status1, $status2) =>
            $status1->order_reverse > $status2->order_reverse ? -1 : ($status1->order_reverse < $status2->order_reverse ? 1 : ($status1->id < $status2->id ? -1 : ($status1->id > $status2->id ? 1 : 0))))
            ->first();
    }
}
