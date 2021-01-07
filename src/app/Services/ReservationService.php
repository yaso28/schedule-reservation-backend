<?php

namespace App\Services;

use App\Repositories\ScheduleReservationRepository;
use Illuminate\Support\Carbon;
use App\Models\Reservation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Exceptions\MyException;

class ReservationService
{
    protected $scheduleReservationRepository;
    protected $adjustReservationStatusService;

    public function __construct(
        ScheduleReservationRepository $scheduleReservationRepository,
        AdjustReservationStatusService $adjustReservationStatusService
    ) {
        $this->scheduleReservationRepository = $scheduleReservationRepository;
        $this->adjustReservationStatusService = $adjustReservationStatusService;
    }

    public function getMonthList($yearFrom = null, $monthFrom = null)
    {
        $yearFromInt = intval($yearFrom);
        $monthFromInt = intval($monthFrom);
        if (!($yearFromInt && $monthFromInt)) {
            $today = Carbon::today();
            $yearFromInt = $today->year;
            $monthFromInt = $today->month;
        }
        return $this->scheduleReservationRepository->selectMonthList($yearFromInt, $monthFromInt);
    }

    public function getMonth($id)
    {
        return $this->scheduleReservationRepository->selectMonth($id);
    }

    public function getScheduleList($isPublicOnly, $from = null, $to = null)
    {
        $fromInput = $from;
        $toInput = $to;
        if (!$fromInput) {
            $fromInput = Carbon::today()->format(FormatService::DATE_FORMAT);
        }
        return $this->scheduleReservationRepository->selectScheduleList($isPublicOnly, $fromInput, $toInput);
    }

    public function getSchedule($id)
    {
        return $this->scheduleReservationRepository->selectSchedule($id);
    }

    public function addScheduleList($attributes)
    {
        $attributesExceptYmd = Arr::except($attributes, 'ymd_list');
        $ymdList = collect($attributes['ymd_list'])->unique()->sort()->values();
        $scheduleAttributesList = $ymdList->map(fn ($ymd) => Arr::add($attributesExceptYmd, 'ymd', $ymd));
        DB::transaction(function () use ($scheduleAttributesList) {
            foreach ($scheduleAttributesList as $scheduleAttributes) {
                $scheduleId = $this->scheduleReservationRepository->insertSchedule($scheduleAttributes);
                $reservationAttributes = Arr::only($scheduleAttributes, ['begins_at', 'ends_at', 'reservation_status_id']);
                $reservationAttributes['schedule_id'] = $scheduleId;
                $this->scheduleReservationRepository->insertReservation($reservationAttributes);
            }
        });

        $this->adjustReservationStatusService->adjustMonthViaYmdList($ymdList);
    }

    public function updateSchedule($id, $attributes)
    {
        $scheduleAttributes = Arr::except($attributes, 'reservation_list');
        $reservationAttributesList = $attributes['reservation_list'];

        $ymdList = $this->scheduleReservationRepository->selectScheduleYmdListViaScheduleIdList([$id]);
        if (array_key_exists('ymd', $scheduleAttributes)) {
            $ymdList->add($scheduleAttributes['ymd']);
        }

        DB::transaction(function () use ($id, $scheduleAttributes, $reservationAttributesList) {
            $setEquals = function ($collection1, $collection2) {
                return $collection1->diff($collection2)->isEmpty() && $collection2->diff($collection1)->isEmpty();
            };
            $inputReservationIdList = collect($reservationAttributesList)
                ->map(fn ($reservationAttributes) => $reservationAttributes['id']);
            $dbReservationIdList = $this->scheduleReservationRepository->selectReservationListViaScheduleId($id)
                ->map(fn ($reservation) => $reservation->id);
            if (!$setEquals($inputReservationIdList, $dbReservationIdList)) {
                throw new MyException;
            }

            $this->scheduleReservationRepository->updateSchedule($id, $scheduleAttributes);
            foreach ($reservationAttributesList as $reservationAttributes) {
                $this->scheduleReservationRepository->updateReservation(
                    $reservationAttributes['id'],
                    Arr::except($reservationAttributes, 'id')
                );
            }
        });

        $this->adjustReservationStatusService->adjustScheduleViaScheduleId($id);
        $this->adjustReservationStatusService->adjustMonthViaYmdList($ymdList);
        return $id;
    }

    public function bulkChangeSchedule($idList, $attributes)
    {
        $ymdList = $this->scheduleReservationRepository->selectScheduleYmdListViaScheduleIdList($idList);
        if (array_key_exists('ymd', $attributes)) {
            $ymdList->add($attributes['ymd']);
        }

        $this->scheduleReservationRepository->bulkUpdateSchedule($idList, $attributes);

        $this->adjustReservationStatusService->adjustScheduleViaScheduleIdList($idList);
        $this->adjustReservationStatusService->adjustMonthViaYmdList($ymdList);
    }

    public function getReservationList($from = null, $to = null)
    {
        $fromInput = $from;
        $toInput = $to;
        if (!$fromInput) {
            $fromInput = Carbon::today()->format(FormatService::DATE_FORMAT);
        }
        return $this->scheduleReservationRepository->selectReservationList($fromInput, $toInput);
    }

    public function getReservationListForSchedule($scheduleId)
    {
        return $this->scheduleReservationRepository->selectReservationListViaScheduleId($scheduleId);
    }

    public function getReservation($id)
    {
        return $this->scheduleReservationRepository->selectReservation($id);
    }

    public function splitReservation($id, $splitsAt)
    {
        $reservation = $this->scheduleReservationRepository->selectReservation($id);
        if (!($reservation->begins_at < $splitsAt && $splitsAt < $reservation->ends_at)) {
            throw ValidationException::withMessages([
                'splits_at' => __('validation.time_order'),
            ]);
        }

        $newReservation = new Reservation;
        $newReservation->schedule_id = $reservation->schedule_id;
        $newReservation->begins_at = $splitsAt;
        $newReservation->ends_at = $reservation->ends_at;
        $newReservation->reservation_status_id = $reservation->reservation_status_id;

        $reservation->ends_at = $splitsAt;

        $this->scheduleReservationRepository->saveEntityList([$reservation, $newReservation]);

        return $reservation->schedule_id;
    }

    public function bulkChangeReservation($idList, $attributes)
    {
        $scheduleIdList = $this->scheduleReservationRepository->selectScheduleIdListViaReservationIdList($idList);
        $ymdList = $this->scheduleReservationRepository->selectScheduleYmdListViaScheduleIdList($scheduleIdList);

        $this->scheduleReservationRepository->bulkUpdateReservation($idList, $attributes);

        $this->adjustReservationStatusService->adjustScheduleViaScheduleIdList($scheduleIdList);
        $this->adjustReservationStatusService->adjustMonthViaYmdList($ymdList);
    }
}
