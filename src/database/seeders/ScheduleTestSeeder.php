<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Services\FormatService;
use App\Models\ScheduleStatus;
use App\Models\ReservationStatus;
use App\Models\SchedulePlace;
use App\Models\ScheduleUsage;
use App\Models\ScheduleTimetable;
use App\Models\Schedule;
use App\Models\Reservation;
use App\Models\Month;

class ScheduleTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            SchedulePlaceTestSeeder::class,
            ScheduleUsageTestSeeder::class,
            ScheduleTimetableTestSeeder::class,
        ]);

        $scheduleStatusList = ScheduleStatus::orderBy('order_reverse', 'desc')->orderBy('id')->get();
        $scheduleStatusMitei = $scheduleStatusList[0];
        $scheduleStatusKakutei = $scheduleStatusList[1];
        $scheduleStatusChushi = $scheduleStatusList[2];
        $scheduleStatusHikokai = $scheduleStatusList[3];

        $reservationStatusList = ReservationStatus::orderBy('order_reverse', 'desc')->orderBy('id')->get();
        $reservationStatusMiyoyaku = $reservationStatusList[0];
        $reservationStatusDenwwa = $reservationStatusList[1];
        $reservationStatusShoruiTeishutsu = $reservationStatusList[2];
        $reservationStatusShoruiHikae = $reservationStatusList[3];
        $reservationStatusRyokin = $reservationStatusList[4];
        $reservationStatusKaikei = $reservationStatusList[5];
        $reservationStatusCancel = $reservationStatusList[6];

        $placeList = SchedulePlace::orderBy('order_reverse', 'desc')->orderBy('id')->get();
        $placeTaiikukan = $placeList[0];
        $placeHall = $placeList[1];
        $placeKaigi = $placeList[2];

        $usageList = ScheduleUsage::orderBy('order_reverse', 'desc')->orderBy('id')->get();
        $usageShoshin = $usageList[0];
        $usageJokyu = $usageList[1];
        $usageTaiken = $usageList[2];
        $usageKaigi = $usageList[3];

        $timetableList = ScheduleTimetable::orderBy('order_reverse', 'desc')->orderBy('id')->get();
        $timetableAm = $timetableList[0];
        $timetablePm = $timetableList[1];
        $timetableAll = $timetableList[2];

        $lastMonth = Carbon::today()->subMonth()->firstOfMonth();
        $thisMonth = Carbon::today()->firstOfMonth();
        $oneMonthLater = Carbon::today()->addMonth()->firstOfMonth();
        $twoMonthsLater = Carbon::today()->addMonths(2)->firstOfMonth();
        $threeMonthsLater = Carbon::today()->addMonths(3)->firstOfMonth();
        $currentDay = $lastMonth->copy();
        for (; $currentDay->lessThan($threeMonthsLater);) {
            $ymd = $currentDay->format(FormatService::DATE_FORMAT);
            switch ($currentDay->weekday()) {
                case Carbon::SATURDAY:
                    $scheduleTaiken = Schedule::create([
                        'ymd' => $ymd,
                        'begins_at' => '10:00',
                        'ends_at' => '13:00',
                        'schedule_place_id' => $placeHall->id,
                        'schedule_usage_id' => $usageTaiken->id,
                        'schedule_timetable_id' => null,
                        'reservation_status_id' => $currentDay->lessThan($thisMonth) ? $reservationStatusKaikei->id : ($currentDay->lessThan($oneMonthLater) ? $reservationStatusRyokin->id : ($currentDay->lessThan($twoMonthsLater) ? $reservationStatusShoruiHikae->id : $reservationStatusShoruiTeishutsu->id)),
                        'schedule_status_id' => $currentDay->lessThan($oneMonthLater) ? $scheduleStatusKakutei->id : $scheduleStatusMitei->id,
                    ]);
                    $this->createReservation($scheduleTaiken);
                    $scheduleShoshin = Schedule::create([
                        'ymd' => $ymd,
                        'begins_at' => '10:00',
                        'ends_at' => '13:00',
                        'schedule_place_id' => $placeTaiikukan->id,
                        'schedule_usage_id' => $usageShoshin->id,
                        'schedule_timetable_id' => $timetableAm->id,
                        'reservation_status_id' => $currentDay->lessThan($thisMonth) ? $reservationStatusKaikei->id : ($currentDay->lessThan($oneMonthLater) ? $reservationStatusRyokin->id : ($currentDay->lessThan($twoMonthsLater) ? $reservationStatusDenwwa->id : $reservationStatusMiyoyaku->id)),
                        'schedule_status_id' => $currentDay->lessThan($oneMonthLater) ? $scheduleStatusKakutei->id : $scheduleStatusMitei->id,
                    ]);
                    $this->createReservation($scheduleShoshin);
                    $scheduleJokyu = Schedule::create([
                        'ymd' => $ymd,
                        'begins_at' => '13:00',
                        'ends_at' => '16:00',
                        'schedule_place_id' => $placeTaiikukan->id,
                        'schedule_usage_id' => $usageJokyu->id,
                        'schedule_timetable_id' => $timetablePm->id,
                        'reservation_status_id' => $currentDay->lessThan($thisMonth) ? $reservationStatusKaikei->id : ($currentDay->lessThan($oneMonthLater) ? $reservationStatusRyokin->id : ($currentDay->lessThan($twoMonthsLater) ? $reservationStatusShoruiHikae->id : $reservationStatusShoruiTeishutsu->id)),
                        'schedule_status_id' => $currentDay->lessThan($oneMonthLater) ? $scheduleStatusKakutei->id : $scheduleStatusMitei->id,
                    ]);
                    $this->createReservation($scheduleJokyu);
                    $currentDay->addDay();
                    break;
                case Carbon::SUNDAY:
                    $scheduleJokyu = Schedule::create([
                        'ymd' => $ymd,
                        'begins_at' => '13:00',
                        'ends_at' => '16:00',
                        'schedule_place_id' => $placeTaiikukan->id,
                        'schedule_usage_id' => $usageJokyu->id,
                        'schedule_timetable_id' => $timetablePm->id,
                        'reservation_status_id' => $currentDay->lessThan($thisMonth) ? $reservationStatusKaikei->id : ($currentDay->lessThan($oneMonthLater) ? $reservationStatusRyokin->id : ($currentDay->lessThan($twoMonthsLater) ? $reservationStatusDenwwa->id : $reservationStatusMiyoyaku->id)),
                        'schedule_status_id' => $currentDay->lessThan($oneMonthLater) ? $scheduleStatusKakutei->id : $scheduleStatusMitei->id,
                    ]);
                    $this->createReservation($scheduleJokyu);
                    $scheduleShoshin = Schedule::create([
                        'ymd' => $ymd,
                        'begins_at' => '10:00',
                        'ends_at' => '13:00',
                        'schedule_place_id' => $placeTaiikukan->id,
                        'schedule_usage_id' => $usageShoshin->id,
                        'schedule_timetable_id' => $timetableAm->id,
                        'reservation_status_id' => $reservationStatusCancel->id,
                        'schedule_status_id' => $scheduleStatusChushi->id,
                    ]);
                    $this->createReservation($scheduleShoshin);
                    $scheduleKaigi = Schedule::create([
                        'ymd' => $ymd,
                        'begins_at' => '13:00',
                        'ends_at' => '16:00',
                        'schedule_place_id' => $placeKaigi->id,
                        'schedule_usage_id' => $usageKaigi->id,
                        'schedule_timetable_id' => null,
                        'reservation_status_id' => $currentDay->lessThan($thisMonth) ? $reservationStatusKaikei->id : ($currentDay->lessThan($oneMonthLater) ? $reservationStatusRyokin->id : ($currentDay->lessThan($twoMonthsLater) ? $reservationStatusShoruiHikae->id : $reservationStatusShoruiTeishutsu->id)),
                        'schedule_status_id' => $scheduleStatusHikokai->id,
                    ]);
                    $this->createReservation($scheduleKaigi);
                    $currentDay->addDay();
                    break;
                case Carbon::MONDAY:
                    $scheduleJokyu = Schedule::create([
                        'ymd' => $ymd,
                        'begins_at' => '10:00',
                        'ends_at' => '16:00',
                        'schedule_place_id' => $placeTaiikukan->id,
                        'schedule_usage_id' => $usageJokyu->id,
                        'schedule_timetable_id' => $timetableAll->id,
                        'reservation_status_id' => $currentDay->lessThan($thisMonth) ? $reservationStatusKaikei->id : ($currentDay->lessThan($oneMonthLater) ? $reservationStatusRyokin->id : ($currentDay->lessThan($twoMonthsLater) ? $reservationStatusDenwwa->id : $reservationStatusMiyoyaku->id)),
                        'schedule_status_id' => $currentDay->lessThan($oneMonthLater) ? $scheduleStatusKakutei->id : $scheduleStatusMitei->id,
                    ]);
                    $this->createReservation($scheduleJokyu, [
                        'begins_at' => '13:00',
                    ]);
                    $this->createReservation($scheduleJokyu, [
                        'ends_at' => '13:00',
                        'reservation_status_id' => $currentDay->lessThan($thisMonth) ? $reservationStatusKaikei->id : ($currentDay->lessThan($oneMonthLater) ? $reservationStatusRyokin->id : ($currentDay->lessThan($twoMonthsLater) ? $reservationStatusShoruiHikae->id : $reservationStatusShoruiTeishutsu->id)),
                    ]);
                    $currentDay->endOfWeek(Carbon::SATURDAY);
                    break;
                default:
                    $currentDay->endOfWeek(Carbon::SATURDAY);
                    break;
            }
        }

        $this->createMonth($lastMonth, $reservationStatusKaikei, $scheduleStatusKakutei);
        $this->createMonth($thisMonth, $reservationStatusRyokin, $scheduleStatusKakutei);
        $this->createMonth($oneMonthLater, $reservationStatusDenwwa, $scheduleStatusMitei);
        $this->createMonth($twoMonthsLater, $reservationStatusMiyoyaku, $scheduleStatusMitei);
    }

    protected function createReservation($schedule, $customAttributes = [])
    {
        $getValue = fn ($key) => (array_key_exists($key, $customAttributes) ? $customAttributes[$key] : $schedule[$key]);
        Reservation::create([
            'schedule_id' => $schedule->id,
            'begins_at' => $getValue('begins_at'),
            'ends_at' => $getValue('ends_at'),
            'reservation_status_id' => $getValue('reservation_status_id'),
        ]);
    }

    protected function createMonth($day, $reservationStatus, $scheduleStatus)
    {
        Month::factory()->create([
            'year' => $day->year,
            'month' => $day->month,
            'reservation_status_id' => $reservationStatus->id,
            'schedule_status_id' => $scheduleStatus->id,
        ]);
    }
}
