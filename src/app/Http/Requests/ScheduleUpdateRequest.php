<?php

namespace App\Http\Requests;

use App\Services\FormatService;
use Illuminate\Support\Arr;

class ScheduleUpdateRequest extends RequestBase
{
    protected $keyYmd = 'ymd';
    protected $keyBeginsAt = 'begins_at';
    protected $keyEndsAt = 'ends_at';
    protected $keyPlaceId = 'schedule_place_id';
    protected $keyUsageId = 'schedule_usage_id';
    protected $keyTimetableId = 'schedule_timetable_id';
    protected $keyScheduleStatusId = 'schedule_status_id';
    protected $keyReservationList = 'reservation_list';
    protected $keyId = 'id';
    protected $keyScheduleId = 'schedule_id';
    protected $keyReservationStatusId = 'reservation_status_id';

    protected function getReservationKey($index, $key)
    {
        $keyReservationList = $this->keyReservationList;
        return "{$keyReservationList}.{$index}.{$key}";
    }

    public function rules()
    {
        $dateFormat = FormatService::DATE_FORMAT;
        $timeFormat = FormatService::TIME_FORMAT;
        return [
            $this->keyYmd => "bail|required|date_format:{$dateFormat}",
            $this->keyBeginsAt => "bail|required|date_format:{$timeFormat}",
            $this->keyEndsAt => "bail|required|date_format:{$timeFormat}",
            $this->keyPlaceId => "bail|required|exists:App\Models\SchedulePlace,id",
            $this->keyUsageId => "bail|required|exists:App\Models\ScheduleUsage,id",
            $this->keyTimetableId => "bail|nullable|exists:App\Models\ScheduleTimetable,id",
            $this->keyScheduleStatusId => "bail|required|exists:App\Models\ScheduleStatus,id",
            $this->keyReservationList => "bail|required|array",
            $this->getReservationKey('*', $this->keyId) => "bail|required|distinct",
            $this->getReservationKey('*', $this->keyBeginsAt) => "bail|required|date_format:{$timeFormat}",
            $this->getReservationKey('*', $this->keyEndsAt) => "bail|required|date_format:{$timeFormat}",
            $this->getReservationKey('*', $this->keyReservationStatusId) => "bail|required|exists:App\Models\ReservationStatus,id",
        ];
    }

    public function values()
    {
        $values = $this->only([
            $this->keyYmd,
            $this->keyBeginsAt,
            $this->keyEndsAt,
            $this->keyPlaceId,
            $this->keyUsageId,
            $this->keyTimetableId,
            $this->keyScheduleStatusId,
        ]);
        $values[$this->keyReservationList] = array_map(
            fn ($reservation) => Arr::only(
                $reservation,
                [$this->keyId, $this->keyBeginsAt, $this->keyEndsAt, $this->keyReservationStatusId]
            ),
            $this->input($this->keyReservationList)
        );
        return $values;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->errors()->hasAny([
                $this->keyBeginsAt,
                $this->keyEndsAt,
                $this->keyReservationList,
                $this->getReservationKey('*', $this->keyBeginsAt),
                $this->getReservationKey('*', $this->keyEndsAt),
            ])) {
                $scheduleBeginsAt = $this->input($this->keyBeginsAt);
                $scheduleEndsAt = $this->input($this->keyEndsAt);
                if ($scheduleBeginsAt >= $scheduleEndsAt) {
                    $this->addTimeOrderError($validator, [
                        $this->keyBeginsAt,
                        $this->keyEndsAt,
                    ]);
                }

                $reservationList = $this->input($this->keyReservationList);
                $reservationLastIndex = count($reservationList) - 1;
                $checkTime = $scheduleBeginsAt;
                for ($i = 0; $i <= $reservationLastIndex; $i++) {
                    $reservationBeginsAt = $reservationList[$i][$this->keyBeginsAt];
                    $reservationEndsAt = $reservationList[$i][$this->keyEndsAt];
                    if ($reservationBeginsAt >= $reservationEndsAt) {
                        $this->addTimeOrderError($validator, [
                            $this->getReservationKey($i, $this->keyBeginsAt),
                            $this->getReservationKey($i, $this->keyEndsAt),
                        ]);
                    }
                    if ($checkTime !== $reservationBeginsAt) {
                        $this->addTimeOrderError($validator, [
                            $i > 0 ? $this->getReservationKey($i - 1, $this->keyEndsAt) : $this->keyBeginsAt,
                            $this->getReservationKey($i, $this->keyBeginsAt),
                        ]);
                    }
                    $checkTime = $reservationEndsAt;
                }

                if ($checkTime !== $scheduleEndsAt) {
                    $this->addTimeOrderError($validator, [
                        $this->getReservationKey($reservationLastIndex, $this->keyEndsAt),
                        $this->keyEndsAt,
                    ]);
                }
            }
        });
    }

    protected function addTimeOrderError($validator, $keyList)
    {
        foreach ($keyList as $key) {
            if (!$validator->errors()->hasAny($key)) {
                $validator->errors()->add($key, __('validation.time_order'));
            }
        }
    }
}
