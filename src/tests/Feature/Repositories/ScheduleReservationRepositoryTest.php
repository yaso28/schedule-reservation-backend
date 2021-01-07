<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Repositories\ScheduleReservationRepository;
use App\Models\Month;
use App\Models\Schedule;
use App\Models\Reservation;
use App\Models\SchedulePlace;
use App\Models\ScheduleTimetable;
use App\Models\ScheduleUsage;
use App\Models\ScheduleStatus;
use App\Models\ReservationStatus;
use Illuminate\Support\Carbon;
use App\Services\FormatService;

class ScheduleReservationRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $scheduleTable = 'schedule';
    protected $reservationTable = 'reservation';
    protected $placeTable = 'schedule_place';
    protected $placeList;
    protected $timetableTable = 'schedule_timetable';
    protected $timetableList;
    protected $usageTable = 'schedule_usage';
    protected $usageList;
    protected $reservationStatusTable = 'reservation_status';
    protected $reservationStatusList;
    protected $scheduleStatusTable = 'schedule_status';
    protected $scheduleStatusList;

    protected $clmYear = 'year';
    protected $clmMonth = 'month';
    protected $clmYmd = 'ymd';
    protected $clmBeginsAt = 'begins_at';
    protected $clmEndsAt = 'ends_at';
    protected $clmScheduleId;
    protected $clmReservationId;
    protected $clmPlaceId;
    protected $clmTimetableId;
    protected $clmUsageId;
    protected $clmReservationStatusId;
    protected $clmScheduleStatusId;
    protected $clmName = 'name';
    protected $clmIsPublic = 'is_public';
    protected $clmOrderReverse = 'order_reverse';

    protected function setUp(): void
    {
        parent::setUp();
        $this->clmScheduleId = "{$this->scheduleTable}_id";
        $this->clmReservationId = "{$this->reservationTable}_id";
        $this->clmPlaceId = "{$this->placeTable}_id";
        $this->clmTimetableId = "{$this->timetableTable}_id";
        $this->clmUsageId = "{$this->usageTable}_id";
        $this->clmReservationStatusId = "{$this->reservationStatusTable}_id";
        $this->clmScheduleStatusId = "{$this->scheduleStatusTable}_id";
    }

    protected function getRepository()
    {
        return resolve(ScheduleReservationRepository::class);
    }

    protected function prepareMasterRecords($option = [])
    {
        $getCount = fn ($key, $default) => array_key_exists($key, $option) ? $option[$key] : $default;

        $this->placeList = SchedulePlace::factory()->count($getCount($this->placeTable, 0))->create();
        $this->timetableList = ScheduleTimetable::factory()->count($getCount($this->timetableTable, 0))->create();
        $this->usageList = ScheduleUsage::factory()->count($getCount($this->usageTable, 0))->create();
        $this->reservationStatusList = ReservationStatus::factory()->count($getCount($this->reservationStatusTable, 10))->create();
        $this->scheduleStatusList = ScheduleStatus::factory()->count($getCount($this->scheduleStatusTable, 5))->create();
    }

    protected function formatTimeOfRecordList($recordList)
    {
        foreach ($recordList as $record) {
            $this->formatTimeOfRecord($record);
        }
        return $recordList;
    }

    protected function formatTimeOfRecord($record)
    {
        $this->formatRecordTime($record, $this->clmBeginsAt);
        $this->formatRecordTime($record, $this->clmEndsAt);
        return $record;
    }

    public function testSaveEntity_Update()
    {
        $reservationStatus = ReservationStatus::factory()->create();
        $schedule = Schedule::factory()->create();
        $schedule->reservation_status_id = $reservationStatus->id;

        $actualReturn = $this->getRepository()->saveEntity($schedule);
        $result = Schedule::find($schedule->id);
        $this->formatTimeOfRecord($result);

        $this->assertDbRecordEquals($schedule, $result);
        $this->assertEquals($schedule->id, $actualReturn);
    }

    public function testSelectMonthList()
    {
        $createRecord = function ($year, $month, $reservationStatus, $scheduleStatus) {
            $record = Month::factory()->create([
                $this->clmYear => $year,
                $this->clmMonth => $month,
                $this->clmReservationStatusId => $reservationStatus->id,
                $this->clmScheduleStatusId => $scheduleStatus->id,
            ]);
            $record->reservation_status;
            $record->schedule_status;
            return $record;
        };
        $this->prepareMasterRecords();
        $thisYear = Carbon::today()->year;
        $thisYearMonthList = [];
        $nextYearMonthList = [];
        for ($month = 12; $month >= 1; $month--) {
            $thisYearMonthList[$month] = $createRecord($thisYear, $month, $this->faker->randomElement($this->reservationStatusList), $this->faker->randomElement($this->scheduleStatusList));
            $nextYearMonthList[$month] = $createRecord($thisYear + 1, $month, $this->faker->randomElement($this->reservationStatusList), $this->faker->randomElement($this->scheduleStatusList));
        }
        $thisMonth = 8;

        $actual = $this->getRepository()->selectMonthList($thisYear, $thisMonth);
        $expected = [];
        for ($month = $thisMonth; $month <= 12; $month++) {
            array_push($expected, $thisYearMonthList[$month]);
        }
        for ($month = 1; $month <= 12; $month++) {
            array_push($expected, $nextYearMonthList[$month]);
        }

        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testSelectMonth()
    {
        $expected = Month::factory()->create();
        $expected->reservation_status;
        $expected->schedule_status;
        $actual = $this->getRepository()->selectMonth($expected->id);
        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testSelectMonthViaYm()
    {
        $year = 2020;
        $month = 11;
        $expected = Month::factory()->create([
            'year' => $year,
            'month' => $month,
        ]);
        $expected->reservation_status;
        $expected->schedule_status;
        $actual = $this->getRepository()->selectMonthViaYm($year, $month);
        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testSelectMonthViaYm_NoRecord()
    {
        $this->assertNull($this->getRepository()->selectMonthViaYm(1999, 1));
    }

    protected function makeDataForScheduleList($ymdFrom, $ymdTo, $isPublicOnly, $limitsTo)
    {
        $ymdBeforeFrom = $ymdFrom->copy()->subDay();
        $ymdAfterTo = $ymdTo->copy()->addDay();
        $ymdMid = $ymdFrom->copy()->addDays(intdiv($ymdFrom->diffInDays($ymdTo), 2));

        $createUsage = fn ($name, $isPublic, $orderReverse) => ScheduleUsage::factory()->create([
            $this->clmName => $name,
            $this->clmIsPublic => $isPublic,
            $this->clmOrderReverse => $orderReverse,
        ]);
        $usage1_ord1_public = $createUsage('用途1-order1-public', true, 1);
        $usage2_ord1_notPublic = $createUsage('用途2-order1-NOTpublic', false, 1);
        $usage3_ord2_public = $createUsage('用途3-order2-public', true, 2);
        $usage4_ord2_public = $createUsage('用途4-order2-public', true, 2);

        $createScheduleStatus = fn ($name, $isPublic) => ScheduleStatus::factory()->create([
            $this->clmName => $name,
            $this->clmIsPublic => $isPublic,
        ]);
        $scheduleStatus_public = $createScheduleStatus('予約ステータス-public', true);
        $scheduleStatus_notPublic = $createScheduleStatus('予約ステータス-NOTpublic', false);

        $place = SchedulePlace::factory()->create();
        $timetable = ScheduleTimetable::factory()->create();
        $reservationStatus = ReservationStatus::factory()->create();

        $time10 = '10:00';
        $time13 = '13:00';
        $time16 = '16:00';

        $createRecord = fn ($ymd, $beginsAt, $endsAt, $usage, $scheduleStatus) => Schedule::factory()->create([
            $this->clmYmd => $ymd->format(FormatService::DATE_FORMAT),
            $this->clmBeginsAt => $beginsAt,
            $this->clmEndsAt => $endsAt,
            $this->clmPlaceId => $place->id,
            $this->clmTimetableId => $timetable->id,
            $this->clmUsageId => $usage->id,
            $this->clmReservationStatusId => $reservationStatus->id,
            $this->clmScheduleStatusId => $scheduleStatus->id,
        ]);
        $record_mid_1013_u2o1np_sp = $createRecord($ymdMid, $time10, $time13, $usage2_ord1_notPublic, $scheduleStatus_public);
        $record_from_1316_u1o1p_snp = $createRecord($ymdFrom, $time13, $time16, $usage1_ord1_public, $scheduleStatus_notPublic);
        $record_mid_1013_u4o2p_sp = $createRecord($ymdMid, $time10, $time13, $usage4_ord2_public, $scheduleStatus_public);
        $record_mid_1316_u3o2p_sp = $createRecord($ymdMid, $time13, $time16, $usage3_ord2_public, $scheduleStatus_public);
        $record_to_1013_u1o1p_snp = $createRecord($ymdTo, $time10, $time13, $usage1_ord1_public, $scheduleStatus_notPublic);
        $record_mid_1316_u4o2p_sp = $createRecord($ymdMid, $time13, $time16, $usage4_ord2_public, $scheduleStatus_public);
        $record_mid_1016_u4o2p_sp = $createRecord($ymdMid, $time10, $time16, $usage4_ord2_public, $scheduleStatus_public);
        $record_before_1013_u1o1p_sp = $createRecord($ymdBeforeFrom, $time10, $time13, $usage1_ord1_public, $scheduleStatus_public);
        $record_mid_1016_u1o1p_sp = $createRecord($ymdMid, $time10, $time16, $usage1_ord1_public, $scheduleStatus_public);
        $record_mid_1016_u2o1np_sp = $createRecord($ymdMid, $time10, $time16, $usage2_ord1_notPublic, $scheduleStatus_public);
        $record_to_1316_u1o1p_sp = $createRecord($ymdTo, $time13, $time16, $usage1_ord1_public, $scheduleStatus_public);
        $record_mid_1316_u1o1p_sp = $createRecord($ymdMid, $time13, $time16, $usage1_ord1_public, $scheduleStatus_public);
        $record_after_1013_u1o1p_sp = $createRecord($ymdAfterTo, $time10, $time13, $usage1_ord1_public, $scheduleStatus_public);
        $record_mid_1316_u2o1np_sp = $createRecord($ymdMid, $time13, $time16, $usage2_ord1_notPublic, $scheduleStatus_public);
        $record_mid_1016_u3o2p_sp = $createRecord($ymdMid, $time10, $time16, $usage3_ord2_public, $scheduleStatus_public);
        $record_mid_1013_u1o1p_sp = $createRecord($ymdMid, $time10, $time13, $usage1_ord1_public, $scheduleStatus_public);
        $record_from_1013_u1o1p_sp = $createRecord($ymdFrom, $time10, $time13, $usage1_ord1_public, $scheduleStatus_public);
        $record_mid_1013_u3o2p_sp = $createRecord($ymdMid, $time10, $time13, $usage3_ord2_public, $scheduleStatus_public);

        $expected = [];
        array_push($expected, $record_from_1013_u1o1p_sp);
        if (!$isPublicOnly) array_push($expected, $record_from_1316_u1o1p_snp);
        array_push($expected, $record_mid_1013_u3o2p_sp);
        array_push($expected, $record_mid_1013_u4o2p_sp);
        array_push($expected, $record_mid_1013_u1o1p_sp);
        if (!$isPublicOnly) array_push($expected, $record_mid_1013_u2o1np_sp);
        array_push($expected, $record_mid_1016_u3o2p_sp);
        array_push($expected, $record_mid_1016_u4o2p_sp);
        array_push($expected, $record_mid_1016_u1o1p_sp);
        if (!$isPublicOnly) array_push($expected, $record_mid_1016_u2o1np_sp);
        array_push($expected, $record_mid_1316_u3o2p_sp);
        array_push($expected, $record_mid_1316_u4o2p_sp);
        array_push($expected, $record_mid_1316_u1o1p_sp);
        if (!$isPublicOnly) array_push($expected, $record_mid_1316_u2o1np_sp);
        if (!$isPublicOnly) array_push($expected, $record_to_1013_u1o1p_snp);
        array_push($expected, $record_to_1316_u1o1p_sp);
        if (!$limitsTo) array_push($expected, $record_after_1013_u1o1p_sp);
        return $expected;
    }

    public function testSelectScheduleList_WithYmdTo()
    {
        $ymdFrom = Carbon::today()->subMonth();
        $ymdTo = Carbon::tomorrow()->addMonths(2)->subDays(10);

        $expected = $this->makeDataForScheduleList($ymdFrom, $ymdTo, false, true);
        foreach ($expected as $record) {
            $record->schedule_place;
            $record->schedule_usage;
            $record->schedule_timetable;
            $record->schedule_status;
            $record->reservation_status;
            $record->schedule_usage->reservation_organization;
        }

        $actual = $this->getRepository()->selectScheduleList(false, $ymdFrom->format(FormatService::DATE_FORMAT), $ymdTo->format(FormatService::DATE_FORMAT));
        $this->formatTimeOfRecordList($actual);

        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testSelectScheduleList_IsPublicOnly()
    {
        $ymdFrom = Carbon::today()->addDays(3);
        $ymdTo = Carbon::tomorrow()->addMonths(3);

        $expected = $this->makeDataForScheduleList($ymdFrom, $ymdTo, true, false);
        foreach ($expected as $record) {
            $record->schedule_place;
            $record->schedule_usage;
            $record->schedule_timetable;
            $record->schedule_status;
        }

        $actual = $this->getRepository()->selectScheduleList(true, $ymdFrom->format(FormatService::DATE_FORMAT));
        $this->formatTimeOfRecordList($actual);

        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testSelectScheduleIdListViaReservationIdList()
    {
        $usage = ScheduleUsage::factory()->create();
        $place = SchedulePlace::factory()->create();
        $timetable = ScheduleTimetable::factory()->create();
        $reservationStatus = ReservationStatus::factory()->create();
        $scheduleStatus = ScheduleStatus::factory()->create();

        $scheduleList = Schedule::factory()->count(10)->create([
            $this->clmPlaceId => $place->id,
            $this->clmTimetableId => $timetable->id,
            $this->clmUsageId => $usage->id,
            $this->clmReservationStatusId => $reservationStatus->id,
            $this->clmScheduleStatusId => $scheduleStatus->id,
        ]);
        $schedule1 = $scheduleList[2];
        $schedule2 = $scheduleList[5];
        $schedule3 = $scheduleList[7];

        $createReservation = fn ($schedule) => Reservation::factory()->create([
            $this->clmScheduleId => $schedule->id,
            $this->clmReservationStatusId => $reservationStatus->id,
        ]);
        $reservation1 = $createReservation($schedule2);
        $reservation2 = $createReservation($schedule3);
        $reservation3 = $createReservation($schedule1);
        $reservation4 = $createReservation($schedule1);
        $reservation5 = $createReservation($schedule2);

        $reservationIdList = collect([$reservation2, $reservation5, $reservation1, $reservation3, $reservation4])
            ->map(fn ($reservation) => $reservation->id);
        $actual = $this->getRepository()->selectScheduleIdListViaReservationIdList($reservationIdList);
        $expected = collect([$schedule1, $schedule2, $schedule3])
            ->map(fn ($schedule) => $schedule->id);

        $this->assertEquals($expected, $actual);
    }

    public function testSelectScheduleYmdListViaScheduleIdList()
    {
        $usage = ScheduleUsage::factory()->create();
        $place = SchedulePlace::factory()->create();
        $timetable = ScheduleTimetable::factory()->create();
        $reservationStatus = ReservationStatus::factory()->create();
        $scheduleStatus = ScheduleStatus::factory()->create();

        $createRecord = fn ($ymd) => Schedule::factory()->create([
            $this->clmYmd => $ymd,
            $this->clmPlaceId => $place->id,
            $this->clmTimetableId => $timetable->id,
            $this->clmUsageId => $usage->id,
            $this->clmReservationStatusId => $reservationStatus->id,
            $this->clmScheduleStatusId => $scheduleStatus->id,
        ]);
        $ymdList = collect([
            '2020-11-30',
            '2020-12-01',
            '2020-12-15',
            '2020-12-31',
            '2021-01-01',
        ]);
        $scheduleIdList = collect([
            $createRecord($ymdList[2]),
            $createRecord($ymdList[1]),
            $createRecord($ymdList[3]),
            $createRecord($ymdList[0]),
            $createRecord($ymdList[1]),
            $createRecord($ymdList[1]),
            $createRecord($ymdList[4]),
            $createRecord($ymdList[2]),
            $createRecord($ymdList[1]),
            $createRecord($ymdList[3]),
        ])->map(fn ($record) => $record->id)->shuffle();

        $actual = $this->getRepository()->selectScheduleYmdListViaScheduleIdList($scheduleIdList);
        $this->assertEquals($ymdList, $actual);
    }

    public function testSelectSchedule()
    {
        $expected = Schedule::factory()->create();
        $expected->schedule_place;
        $expected->schedule_usage;
        $expected->schedule_timetable;
        $expected->schedule_status;
        $expected->reservation_status;
        $expected->schedule_usage->reservation_organization;

        $actual = $this->getRepository()->selectSchedule($expected->id);
        $this->formatTimeOfRecord($actual);

        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testInsertSchedule()
    {
        $attributes = [
            $this->clmYmd => '2020-12-02',
            $this->clmBeginsAt => '10:00',
            $this->clmEndsAt => '13:00',
            $this->clmPlaceId => SchedulePlace::factory()->create()->id,
            $this->clmUsageId => ScheduleUsage::factory()->create()->id,
            $this->clmTimetableId => ScheduleTimetable::factory()->create()->id,
            $this->clmReservationStatusId => ReservationStatus::factory()->create()->id,
            $this->clmScheduleStatusId => ScheduleStatus::factory()->create()->id,
        ];
        $actualReturn = $this->getRepository()->insertSchedule($attributes);
        $actualInserted = $this->formatTimeOfRecord($this->selectInserted(Schedule::class));
        $this->assertChangedDbRecord($attributes, $actualInserted);
        $this->assertEquals($actualInserted->id, $actualReturn);
    }

    public function testUpdateSchedule()
    {
        $attributes = [
            $this->clmYmd => '2020-12-02',
            $this->clmBeginsAt => '10:00',
            $this->clmEndsAt => '13:00',
            $this->clmPlaceId => SchedulePlace::factory()->create()->id,
            $this->clmUsageId => ScheduleUsage::factory()->create()->id,
            $this->clmTimetableId => ScheduleTimetable::factory()->create()->id,
            $this->clmReservationStatusId => ReservationStatus::factory()->create()->id,
            $this->clmScheduleStatusId => ScheduleStatus::factory()->create()->id,
        ];
        $id = Schedule::factory()->create()->id;
        $actualReturn = $this->getRepository()->updateSchedule($id, $attributes);
        $actualUpdated = $this->formatTimeOfRecord(Schedule::find($id));
        $this->assertChangedDbRecord($attributes, $actualUpdated);
        $this->assertEquals($id, $actualReturn);
    }

    public function testBulkUpdateSchedule()
    {
        $this->prepareMasterRecords([
            $this->placeTable => 5,
            $this->timetableTable => 5,
            $this->usageTable => 5,
        ]);
        $scheduleList = collect();
        for ($i = 0; $i < 20; $i++) {
            $scheduleList->add(Schedule::factory()->create([
                $this->clmPlaceId => $this->faker->randomElement($this->placeList)->id,
                $this->clmTimetableId => $this->faker->randomElement($this->timetableList)->id,
                $this->clmUsageId => $this->faker->randomElement($this->usageList)->id,
                $this->clmReservationStatusId => $this->faker->randomElement($this->reservationStatusList)->id,
                $this->clmScheduleStatusId => $this->faker->randomElement($this->scheduleStatusList)->id,
            ]));
        }

        $idList = $scheduleList->shuffle()->values()->take(10)->map(fn ($schedule) => $schedule->id)->toArray();
        $attributes = [
            $this->clmYmd => '2020-11-26',
            $this->clmBeginsAt => '10:15',
            $this->clmEndsAt => '17:45',
            $this->clmPlaceId => $this->faker->randomElement($this->placeList)->id,
            $this->clmTimetableId => $this->faker->randomElement($this->timetableList)->id,
            $this->clmUsageId => $this->faker->randomElement($this->usageList)->id,
            $this->clmReservationStatusId => $this->faker->randomElement($this->reservationStatusList)->id,
            $this->clmScheduleStatusId => $this->faker->randomElement($this->scheduleStatusList)->id,
        ];
        $this->getRepository()->bulkUpdateSchedule($idList, $attributes);

        foreach ($idList as $id) {
            $result = Schedule::find($id);
            $this->formatTimeOfRecord($result);
            $this->assertChangedDbRecord($attributes, $result);
        }
    }

    protected function makeDataForReservationList($ymdFrom, $ymdTo, $limitsTo)
    {
        $ymdBeforeFrom = $ymdFrom->copy()->subDay();
        $ymdAfterTo = $ymdTo->copy()->addDay();
        $ymdMid = $ymdFrom->copy()->addDays(intdiv($ymdFrom->diffInDays($ymdTo), 2));

        $usage1_ord1 = ScheduleUsage::factory()->create(['order_reverse' => 1]);
        $usage2_ord1 = ScheduleUsage::factory()->create(['order_reverse' => 1]);
        $usage3_ord2 = ScheduleUsage::factory()->create(['order_reverse' => 2]);

        $scheduleStatus = ScheduleStatus::factory()->create();
        $place = SchedulePlace::factory()->create();
        $timetable = ScheduleTimetable::factory()->create();
        $reservationStatus = ReservationStatus::factory()->create();

        $time10 = '10:00';
        $time13 = '13:00';
        $time16 = '16:00';

        $createScheduleRecord = fn ($ymd, $beginsAt, $endsAt, $usage) => Schedule::factory()->create([
            $this->clmYmd => $ymd->format(FormatService::DATE_FORMAT),
            $this->clmBeginsAt => $beginsAt,
            $this->clmEndsAt => $endsAt,
            $this->clmPlaceId => $place->id,
            $this->clmTimetableId => $timetable->id,
            $this->clmUsageId => $usage->id,
            $this->clmReservationStatusId => $reservationStatus->id,
            $this->clmScheduleStatusId => $scheduleStatus->id,
        ]);
        $createReservationRecord = fn ($beginsAt, $endsAt, $schedule) => Reservation::factory()->create([
            $this->clmScheduleId => $schedule->id,
            $this->clmBeginsAt => $beginsAt,
            $this->clmEndsAt => $endsAt,
            $this->clmReservationStatusId => $reservationStatus->id,
        ]);
        $createRecords = function ($ymd, $beginsAt, $endsAt, $usage, $splitTime = null) use ($createScheduleRecord, $createReservationRecord) {
            $schedule = $createScheduleRecord($ymd, $beginsAt, $endsAt, $usage);
            if ($splitTime) {
                return [
                    $createReservationRecord($splitTime, $endsAt, $schedule),
                    $createReservationRecord($beginsAt, $splitTime, $schedule),
                ];
            } else {
                return [
                    $createReservationRecord($beginsAt, $endsAt, $schedule),
                ];
            }
        };

        $records_mid_1013_u2o1 = $createRecords($ymdMid, $time10, $time13, $usage2_ord1);
        $records_mid_1316_u3o2 = $createRecords($ymdMid, $time13, $time16, $usage3_ord2);
        $records_before_1013_u1o1 = $createRecords($ymdBeforeFrom, $time10, $time13, $usage1_ord1);
        $records_mid_1016_u2o1_split = $createRecords($ymdMid, $time10, $time16, $usage2_ord1, $time13);
        $records_to_1316_u1o1 = $createRecords($ymdTo, $time13, $time16, $usage1_ord1);
        $records_mid_1316_u1o1 = $createRecords($ymdMid, $time13, $time16, $usage1_ord1);
        $records_after_1013_u1o1 = $createRecords($ymdAfterTo, $time10, $time13, $usage1_ord1);
        $records_mid_1316_u2o1 = $createRecords($ymdMid, $time13, $time16, $usage2_ord1);
        $records_mid_1016_u3o2_split = $createRecords($ymdMid, $time10, $time16, $usage3_ord2, $time13);
        $records_mid_1013_u1o1 = $createRecords($ymdMid, $time10, $time13, $usage1_ord1);
        $records_mid_1016_u1o1_split = $createRecords($ymdMid, $time10, $time16, $usage1_ord1, $time13);
        $records_from_1013_u1o1 = $createRecords($ymdFrom, $time10, $time13, $usage1_ord1);
        $records_mid_1013_u3o2 = $createRecords($ymdMid, $time10, $time13, $usage3_ord2);

        $expected = collect()
            ->concat($records_from_1013_u1o1)
            ->concat($records_mid_1013_u3o2)
            ->concat($records_mid_1013_u1o1)
            ->concat($records_mid_1013_u2o1)
            ->concat(array_reverse($records_mid_1016_u3o2_split))
            ->concat(array_reverse($records_mid_1016_u1o1_split))
            ->concat(array_reverse($records_mid_1016_u2o1_split))
            ->concat($records_mid_1316_u3o2)
            ->concat($records_mid_1316_u1o1)
            ->concat($records_mid_1316_u2o1)
            ->concat($records_to_1316_u1o1)
            ->concat($limitsTo ? [] : $records_after_1013_u1o1);
        foreach ($expected as $record) {
            $record->schedule;
            $record->schedule->schedule_place;
            $record->schedule->schedule_usage;
            $record->schedule->schedule_usage->reservation_organization;
            $record->reservation_status;
        }
        return $expected;
    }

    public function testSelectReservationList_WithYmdTo()
    {
        $ymdFrom = Carbon::today()->subMonth();
        $ymdTo = Carbon::tomorrow()->addMonths(2)->subDays(10);

        $expected = $this->makeDataForReservationList($ymdFrom, $ymdTo, true);
        foreach ($expected as $record) {
            $this->formatTimeOfRecord($record);
            $this->formatTimeOfRecord($record->schedule);
        }

        $actual = $this->getRepository()->selectReservationList($ymdFrom->format(FormatService::DATE_FORMAT), $ymdTo->format(FormatService::DATE_FORMAT));
        foreach ($actual as $record) {
            $this->formatTimeOfRecord($record);
            $this->formatTimeOfRecord($record->schedule);
        }

        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testSelectReservationList_WithoutYmdTo()
    {
        $ymdFrom = Carbon::today()->addDays(3);
        $ymdTo = Carbon::tomorrow()->addMonths(3);

        $expected = $this->makeDataForReservationList($ymdFrom, $ymdTo, false);
        foreach ($expected as $record) {
            $this->formatTimeOfRecord($record);
            $this->formatTimeOfRecord($record->schedule);
        }

        $actual = $this->getRepository()->selectReservationList($ymdFrom->format(FormatService::DATE_FORMAT));
        foreach ($actual as $record) {
            $this->formatTimeOfRecord($record);
            $this->formatTimeOfRecord($record->schedule);
        }

        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testSelectReservationListViaScheduleId_Single()
    {
        $schedule = Schedule::factory()->create();
        $reservation = Reservation::factory()->create([$this->clmScheduleId => $schedule->id]);
        $reservation->reservation_status;
        $expected = collect([$reservation]);

        $actual = $this->getRepository()->selectReservationListViaScheduleId($schedule->id);
        $this->formatTimeOfRecordList($actual);

        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testSelectReservationListViaScheduleId_Split()
    {
        $schedule = Schedule::factory()->create();
        $reservationSecond = Reservation::factory()->create([
            $this->clmScheduleId => $schedule->id,
            $this->clmBeginsAt => '13:00',
            $this->clmEndsAt => '16:00',
        ]);
        $reservationFirst = Reservation::factory()->create([
            $this->clmScheduleId => $schedule->id,
            $this->clmBeginsAt => '10:00',
            $this->clmEndsAt => '13:00',
        ]);
        $expected = collect([$reservationFirst, $reservationSecond]);
        foreach ($expected as $reservation) {
            $reservation->reservation_status;
        }

        $actual = $this->getRepository()->selectReservationListViaScheduleId($schedule->id);
        $this->formatTimeOfRecordList($actual);

        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testSelectReservation()
    {
        $expected = Reservation::factory()->create();
        $expected->schedule;
        $expected->schedule->schedule_place;
        $expected->schedule->schedule_usage;
        $expected->schedule->schedule_usage->reservation_organization;
        $expected->reservation_status;

        $actual = $this->getRepository()->selectReservation($expected->id);
        $this->formatTimeOfRecord($actual);

        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testInsertReservation()
    {
        $attributes = [
            $this->clmScheduleId => Schedule::factory()->create()->id,
            $this->clmBeginsAt => '10:00',
            $this->clmEndsAt => '13:00',
            $this->clmReservationStatusId => ReservationStatus::factory()->create()->id,
        ];
        $actualReturn = $this->getRepository()->insertReservation($attributes);
        $actualInserted = $this->formatTimeOfRecord($this->selectInserted(Reservation::class));
        $this->assertChangedDbRecord($attributes, $actualInserted);
        $this->assertEquals($actualInserted->id, $actualReturn);
    }

    public function testUpdateReservation()
    {
        $attributes = [
            $this->clmScheduleId => Schedule::factory()->create()->id,
            $this->clmBeginsAt => '10:00',
            $this->clmEndsAt => '13:00',
            $this->clmReservationStatusId => ReservationStatus::factory()->create()->id,
        ];
        $id = Reservation::factory()->create()->id;
        $actualReturn = $this->getRepository()->updateReservation($id, $attributes);
        $actualUpdated = $this->formatTimeOfRecord(Reservation::find($id));
        $this->assertChangedDbRecord($attributes, $actualUpdated);
        $this->assertEquals($id, $actualReturn);
    }

    public function testBulkUpdateReservation()
    {
        $this->prepareMasterRecords([
            $this->placeTable => 1,
            $this->timetableTable => 1,
            $this->usageTable => 1,
            $this->scheduleStatusTable => 1,
        ]);
        $scheduleList = collect();
        $reservationList = collect();
        for ($i = 0; $i < 20; $i++) {
            $schedule = Schedule::factory()->create([
                $this->clmPlaceId => $this->placeList[0]->id,
                $this->clmTimetableId => $this->timetableList[0]->id,
                $this->clmUsageId => $this->usageList[0]->id,
                $this->clmReservationStatusId => $this->faker->randomElement($this->reservationStatusList)->id,
                $this->clmScheduleStatusId => $this->scheduleStatusList[0]->id,
            ]);
            $scheduleList->add($schedule);
            $reservationList->add(Reservation::factory()->create([
                $this->clmScheduleId => $schedule->id,
                $this->clmReservationStatusId => $schedule[$this->clmReservationStatusId],
            ]));
        }

        $idList = $reservationList->shuffle()->values()->take(10)->map(fn ($reservation) => $reservation->id)->toArray();
        $attributes = [
            $this->clmScheduleId => $this->faker->randomElement($scheduleList)->id,
            $this->clmBeginsAt => '10:15',
            $this->clmEndsAt => '17:45',
            $this->clmReservationStatusId => $this->faker->randomElement($this->reservationStatusList)->id,
        ];
        $this->getRepository()->bulkUpdateReservation($idList, $attributes);

        foreach ($idList as $id) {
            $result = Reservation::find($id);
            $this->formatTimeOfRecord($result);
            $this->assertChangedDbRecord($attributes, $result);
        }
    }
}
