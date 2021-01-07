<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\AdjustReservationStatusService;
use App\Services\ScheduleMasterService;
use App\Repositories\ScheduleReservationRepository;
use App\Models\Schedule;
use App\Models\Reservation;
use App\Models\Month;
use App\Models\ReservationStatus;
use App\Models\ScheduleStatus;
use stdClass;
use Hamcrest\Matchers;

class AdjustReservationStatusServiceTest extends TestCase
{
    protected $clmId = 'id';
    protected $clmYmd = 'ymd';
    protected $clmBeginsAt = 'begins_at';
    protected $clmEndsAt = 'ends_at';
    protected $clmPlaceId = 'schedule_place_id';
    protected $clmUsageId = 'schedule_timetable_id';
    protected $clmTimetableId = 'schedule_usage_id';
    protected $clmReservationStatusId = 'reservation_status_id';
    protected $clmScheduleStatusId = 'schedule_status_id';
    protected $clmScheduleId = 'schedule_id';
    protected $clmYear = 'year';
    protected $clmMonth = 'month';
    protected $clmOrderReverse = 'order_reverse';
    protected $tblReservationStatus = 'reservation_status';
    protected $tblScheduleStatus = 'schedule_status';

    protected function getService()
    {
        return resolve(AdjustReservationStatusService::class);
    }

    protected function mockService($closure)
    {
        $this->partialMockWithArgs(
            AdjustReservationStatusService::class,
            [
                resolve(ScheduleReservationRepository::class),
                resolve(ScheduleMasterService::class),
            ],
            $closure
        );
    }

    protected function makeYm($year, $month)
    {
        return [$this->clmYear => $year, $this->clmMonth => $month];
    }

    protected function makeMonthRecord($attributes = [])
    {
        $getValue = fn ($key, $default) => array_key_exists($key, $attributes) ? $attributes[$key] : $default;
        $record = Month::factory()->make([
            $this->clmYear => $getValue($this->clmYmd, 2020),
            $this->clmMonth => $getValue($this->clmBeginsAt, 11),
            $this->clmReservationStatusId => $getValue($this->clmReservationStatusId, -1),
            $this->clmScheduleStatusId => $getValue($this->clmScheduleStatusId, -1),
        ]);
        $record->id = $getValue($this->clmId, 1);
        $record->reservation_status = $getValue($this->tblReservationStatus, null);
        $record->schedule_status = $getValue($this->tblScheduleStatus, null);
        return $record;
    }

    protected function makeScheduleRecord($attributes = [])
    {
        $getValue = fn ($key, $default) => array_key_exists($key, $attributes) ? $attributes[$key] : $default;
        $record = Schedule::factory()->make([
            $this->clmYmd => $getValue($this->clmYmd, '2020-11-27'),
            $this->clmBeginsAt => $getValue($this->clmBeginsAt, '10:00'),
            $this->clmEndsAt => $getValue($this->clmYmd, '16:00'),
            $this->clmPlaceId => $getValue($this->clmPlaceId, 1),
            $this->clmUsageId => $getValue($this->clmUsageId, 1),
            $this->clmTimetableId => $getValue($this->clmTimetableId, 1),
            $this->clmReservationStatusId => $getValue($this->clmReservationStatusId, -1),
            $this->clmScheduleStatusId => $getValue($this->clmScheduleStatusId, -1),
        ]);
        $record->id = $getValue($this->clmId, 1);
        $record->reservation_status = $getValue($this->tblReservationStatus, null);
        $record->schedule_status = $getValue($this->tblScheduleStatus, null);
        return $record;
    }

    protected function makeReservationRecord($attributes = [])
    {
        $getValue = fn ($key, $default) => array_key_exists($key, $attributes) ? $attributes[$key] : $default;
        $record = Reservation::factory()->make([
            $this->clmScheduleId => $getValue($this->clmScheduleId, 1),
            $this->clmBeginsAt => $getValue($this->clmBeginsAt, '10:00'),
            $this->clmEndsAt => $getValue($this->clmEndsAt, '16:00'),
            $this->clmReservationStatusId => $getValue($this->clmReservationStatusId, -1),
        ]);
        $record->id = $getValue($this->clmId, 1);
        $record->reservation_status = $getValue($this->tblReservationStatus, null);
        return $record;
    }

    protected function makeReservationStatusRecord($id, $orderReverse)
    {
        $record = ReservationStatus::factory()->make([
            $this->clmOrderReverse => $orderReverse,
        ]);
        $record->id = $id;
        return $record;
    }

    protected function makeScheduleStatusRecord($id, $orderReverse)
    {
        $record = ScheduleStatus::factory()->make([
            $this->clmOrderReverse => $orderReverse,
        ]);
        $record->id = $id;
        return $record;
    }

    protected function assertAdjustScheduleViaScheduleIdList($scheduleIdList)
    {
        $this->mockService(function ($mock) use ($scheduleIdList) {
            $indexObj = new stdClass;
            $indexObj->index = 0;
            $mock->shouldReceive('adjustScheduleViaScheduleId')
                ->times(count($scheduleIdList))
                ->andReturnUsing(function ($scheduleId) use ($scheduleIdList, $indexObj) {
                    $index = $indexObj->index++;
                    $this->assertEquals($scheduleIdList[$index], $scheduleId);
                });
        });

        $this->getService()->adjustScheduleViaScheduleIdList($scheduleIdList);
    }

    public function testAdjustScheduleViaScheduleIdList_ArgArray()
    {
        $this->assertAdjustScheduleViaScheduleIdList([1, 4, 3, 7, 5]);
    }

    public function testAdjustScheduleViaScheduleIdList_ArgCollection()
    {
        $this->assertAdjustScheduleViaScheduleIdList(collect([5, 12, 6, 3, 8, 4, 9]));
    }

    public function testAdjustScheduleViaScheduleId()
    {
        $scheduleId = 7;
        $schedule = $this->makeScheduleRecord([$this->clmScheduleId => $scheduleId]);
        $reservationStatus = $this->makeReservationStatusRecord(4, 6);
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($scheduleId, $schedule, $reservationStatus) {
            $mock->shouldReceive('selectSchedule')
                ->once()
                ->with($scheduleId)
                ->andReturn($schedule);
            $mock->shouldReceive('saveEntity')
                ->once()
                ->andReturnUsing(function ($arg) use ($schedule, $reservationStatus) {
                    $this->assertSame($schedule, $arg);
                    $this->assertSame($reservationStatus->id, $arg->reservation_status_id);
                });
        });
        $this->mockService(function ($mock) use ($schedule, $reservationStatus) {
            $mock->shouldReceive('getAdjustedReservationStatusForSchedule')
                ->once()
                ->with($schedule)
                ->andReturn($reservationStatus);
        });

        $this->getService()->adjustScheduleViaScheduleId($scheduleId);
    }

    public function testGetAdjustedReservationStatusForSchedule()
    {
        $makeTestCase = function ($name, $expectedStatus, $reservationAttributesList) {
            $testCase = new stdClass;
            $testCase->name = $name;
            $testCase->expectedStatus = $expectedStatus;
            $scheduleId = 3;
            $testCase->schedule = $this->makeScheduleRecord([
                $this->clmId => $scheduleId,
                $this->clmBeginsAt => '10:00',
                $this->clmEndsAt => '16:00',
                $this->clmReservationStatusId => -1,
            ]);
            $testCase->reservationList = $reservationAttributesList->map(function ($reservationAttributes) use ($scheduleId) {
                $beginsAt = $reservationAttributes[0];
                $endsAt = $reservationAttributes[1];
                $reservationStatus = $reservationAttributes[2];
                return $this->makeReservationRecord([
                    $this->clmScheduleId => $scheduleId,
                    $this->clmBeginsAt => $beginsAt,
                    $this->clmEndsAt => $endsAt,
                    $this->clmReservationStatusId => $reservationStatus->id,
                    $this->tblReservationStatus => $reservationStatus,
                ]);
            });
            return $testCase;
        };
        $status1_init = $this->makeReservationStatusRecord(1, 3);
        $status2_ord1 = $this->makeReservationStatusRecord(2, 1);
        $status3_ord2 = $this->makeReservationStatusRecord(3, 2);
        $status4_ord2 = $this->makeReservationStatusRecord(4, 2);

        $testCaseList = [
            $makeTestCase('予約：0', $status1_init, collect()),
            $makeTestCase('予約：1,時刻：正しい', $status2_ord1, collect([
                ['10:00', '16:00', $status2_ord1],
            ])),
            $makeTestCase('予約：1,時刻：r1.begin < s.begin', $status1_init, collect([
                ['09:45', '16:00', $status2_ord1],
            ])),
            $makeTestCase('予約：1,時刻：r1.begin > s.begin', $status1_init, collect([
                ['10:15', '16:00', $status2_ord1],
            ])),
            $makeTestCase('予約：1,時刻：r1.end < s.end', $status1_init, collect([
                ['10:00', '15:45', $status2_ord1],
            ])),
            $makeTestCase('予約：1,時刻：r1.end > s.end', $status1_init, collect([
                ['10:00', '16:15', $status2_ord1],
            ])),
            $makeTestCase('予約：2,時刻：正しい', $status4_ord2, collect([
                ['10:00', '13:00', $status2_ord1],
                ['13:00', '16:00', $status4_ord2],
            ])),
            $makeTestCase('予約：2,時刻：r1.begin < s.begin', $status1_init, collect([
                ['09:45', '13:00', $status2_ord1],
                ['13:00', '16:00', $status4_ord2],
            ])),
            $makeTestCase('予約：2,時刻：r1.begin > s.begin', $status1_init, collect([
                ['10:15', '13:00', $status2_ord1],
                ['13:00', '16:00', $status4_ord2],
            ])),
            $makeTestCase('予約：2,時刻：r1.end < r2.begin', $status1_init, collect([
                ['10:00', '12:45', $status2_ord1],
                ['13:00', '16:00', $status4_ord2],
            ])),
            $makeTestCase('予約：2,時刻：r1.end > r2.begin', $status1_init, collect([
                ['10:00', '13:00', $status2_ord1],
                ['12:45', '16:00', $status4_ord2],
            ])),
            $makeTestCase('予約：2,時刻：r2.end < s.end', $status1_init, collect([
                ['10:00', '13:00', $status2_ord1],
                ['13:00', '15:45', $status4_ord2],
            ])),
            $makeTestCase('予約：2,時刻：r2.end > s.end', $status1_init, collect([
                ['10:00', '13:00', $status2_ord1],
                ['13:00', '16:15', $status4_ord2],
            ])),
            $makeTestCase('予約：many,時刻：正しい', $status3_ord2, collect([
                ['10:00', '11:00', $status4_ord2],
                ['11:00', '12:00', $status3_ord2],
                ['12:00', '13:00', $status2_ord1],
                ['13:00', '14:00', $status3_ord2],
                ['14:00', '15:00', $status4_ord2],
                ['15:00', '16:00', $status2_ord1],
            ])),
        ];

        foreach ($testCaseList as $testCase) {
            $this->mock(ScheduleReservationRepository::class, function ($mock) use ($testCase) {
                $mock->shouldReceive('selectReservationListViaScheduleId')
                    ->once()
                    ->with($testCase->schedule->id)
                    ->andReturn($testCase->reservationList);
            });
            if ($testCase->expectedStatus === $status1_init) {
                $this->mock(scheduleMasterService::class, function ($mock) use ($status1_init) {
                    $mock->shouldReceive('getInitialReservationStatus')
                        ->once()
                        ->withNoArgs()
                        ->andReturn($status1_init);
                });
            } else {
                $this->mock(scheduleMasterService::class, function ($mock) {
                    $mock->shouldNotReceive('getInitialReservationStatus');
                });
            }

            $result = $this->getService()->getAdjustedReservationStatusForSchedule($testCase->schedule);
            $this->assertSame($testCase->expectedStatus, $result, $testCase->name);
        }
    }

    protected function assertAdjustMonthViaYmdList($ymdList, $ymList)
    {
        $this->mockService(function ($mock) use ($ymList) {
            $mock->shouldReceive('adjustMonthViaYmList')
                ->once()
                ->with(Matchers::equalTo($ymList));
        });
        $this->getService()->adjustMonthViaYmdList($ymdList);
    }

    public function testAdjustMonthViaYmdList_ArgArray()
    {
        $this->assertAdjustMonthViaYmdList(
            [
                '2020-11-07',
                '2020-12-31',
                '2021-01-01',
                '2020-11-01',
                '2019-12-31',
                '2020-12-01',
                '2020-09-30',
                '2020-12-15',
                '2020-11-30',
            ],
            collect([
                ['year' => 2019, 'month' => 12,],
                ['year' => 2020, 'month' => 9,],
                ['year' => 2020, 'month' => 11,],
                ['year' => 2020, 'month' => 12,],
                ['year' => 2021, 'month' => 1,],
            ])
        );
    }

    public function testAdjustMonthViaYmdList_ArgCollection()
    {
        $this->assertAdjustMonthViaYmdList(
            collect([
                '2020-11-07',
                '2020-12-31',
                '2021-01-01',
                '2020-11-01',
                '2019-12-31',
                '2020-12-01',
                '2020-09-30',
                '2020-12-15',
                '2020-11-30',
            ]),
            collect([
                ['year' => 2019, 'month' => 12,],
                ['year' => 2020, 'month' => 9,],
                ['year' => 2020, 'month' => 11,],
                ['year' => 2020, 'month' => 12,],
                ['year' => 2021, 'month' => 1,],
            ])
        );
    }

    public function testAdjustMonthViaYmList()
    {
        $ymList = collect([[2020, 11], [2020, 12], [2021, 1]])
            ->map(fn ($value) => $this->makeYm($value[0], $value[1]));
        $indexObj = new stdClass;
        $indexObj->index = 0;
        $this->mockService(function ($mock) use ($ymList, $indexObj) {
            $mock->shouldReceive('adjustMonthViaYm')
                ->times(count($ymList))
                ->andReturnUsing(function ($ym) use ($ymList, $indexObj) {
                    $index = $indexObj->index++;
                    $this->assertSame($ymList[$index], $ym);
                });
        });

        $this->getService()->adjustMonthViaYmList($ymList);
    }

    public function testAdjustMonthViaYm()
    {
        $makeTestCase = function ($name, $expectedReservationStatus, $expectedScheduleStatus, $existsMonthRecord, $statusPairListForScheduleList) {
            $testCase = new stdClass;
            $testCase->name = $name;
            $testCase->argYear = 2020;
            $testCase->argMonth = 11;
            $testCase->monthModel = $this->makeMonthRecord([$this->clmYear => $testCase->argYear, $this->clmMonth => $testCase->argMonth]);
            $testCase->existingMonthRecord = $existsMonthRecord ? $testCase->monthModel : null;
            $testCase->existingScheduleRecordList = collect($statusPairListForScheduleList)
                ->map(fn ($statusPair) => $this->makeScheduleRecord([
                    $this->clmReservationStatusId => $statusPair[0]->id,
                    $this->tblReservationStatus => $statusPair[0],
                    $this->clmScheduleStatusId => $statusPair[1]->id,
                    $this->tblScheduleStatus => $statusPair[1],
                ]));
            $testCase->expectedReservationStatus = $expectedReservationStatus;
            $testCase->expectedScheduleStatus = $expectedScheduleStatus;
            return $testCase;
        };
        $r_status1_init = $this->makeReservationStatusRecord(1, 4);
        $r_status2_ord2 = $this->makeReservationStatusRecord(2, 2);
        $r_status3_ord3 = $this->makeReservationStatusRecord(3, 3);
        $r_status4_ord3 = $this->makeReservationStatusRecord(4, 3);
        $r_status5_ord1 = $this->makeReservationStatusRecord(5, 1);
        $s_status1_init = $this->makeScheduleStatusRecord(1, 4);
        $s_status2_ord3 = $this->makeScheduleStatusRecord(2, 3);
        $s_status3_ord1 = $this->makeScheduleStatusRecord(3, 1);
        $s_status4_ord2 = $this->makeScheduleStatusRecord(4, 2);
        $s_status5_ord3 = $this->makeScheduleStatusRecord(5, 3);

        $testCaseList = [
            $makeTestCase('月：なし,予定：なし', $r_status1_init, $s_status1_init, false, []),
            $makeTestCase('月：あり,予定：なし', $r_status1_init, $s_status1_init, true, []),
            $makeTestCase('月：あり,予定：あり', $r_status3_ord3, $s_status2_ord3, true, [
                [$r_status4_ord3, $s_status3_ord1],
                [$r_status2_ord2, $s_status4_ord2],
                [$r_status5_ord1, $s_status5_ord3],
                [$r_status3_ord3, $s_status3_ord1],
                [$r_status4_ord3, $s_status4_ord2],
                [$r_status5_ord1, $s_status4_ord2],
                [$r_status2_ord2, $s_status5_ord3],
                [$r_status2_ord2, $s_status3_ord1],
                [$r_status5_ord1, $s_status2_ord3],
                [$r_status4_ord3, $s_status5_ord3],
            ]),
        ];

        foreach ($testCaseList as $testCase) {
            $this->mock(ScheduleReservationRepository::class, function ($mock) use ($testCase) {
                $mock->shouldReceive('selectMonthViaYm')
                    ->once()
                    ->with($testCase->argYear, $testCase->argMonth)
                    ->andReturn($testCase->existingMonthRecord);
                $mock->shouldReceive('selectScheduleList')
                    ->once()
                    ->with(false, $testCase->monthModel->first_day, $testCase->monthModel->last_day)
                    ->andReturn($testCase->existingScheduleRecordList);
                $mock->shouldReceive('saveEntity')
                    ->once()
                    ->andReturnUsing(function ($arg) use ($testCase) {
                        if ($testCase->existingMonthRecord) {
                            $this->assertSame($testCase->existingMonthRecord, $arg);
                        } else {
                            $this->assertNull($arg->id);
                        }
                        $this->assertEquals($testCase->argYear, $arg->year);
                        $this->assertEquals($testCase->argMonth, $arg->month);
                        $this->assertEquals($testCase->expectedReservationStatus->id, $arg->reservation_status_id);
                        $this->assertEquals($testCase->expectedScheduleStatus->id, $arg->schedule_status_id);
                    });
            });
            $this->mock(scheduleMasterService::class, function ($mock) use ($testCase, $r_status1_init, $s_status1_init) {
                if (count($testCase->existingScheduleRecordList)) {
                    $mock->shouldNotReceive('getInitialReservationStatus');
                    $mock->shouldNotReceive('getInitialScheduleStatus');
                } else {
                    $mock->shouldReceive('getInitialReservationStatus')
                        ->once()
                        ->withNoArgs()
                        ->andReturn($r_status1_init);
                    $mock->shouldReceive('getInitialScheduleStatus')
                        ->once()
                        ->withNoArgs()
                        ->andReturn($s_status1_init);
                }
            });

            $this->getService()->adjustMonthViaYm($this->makeYm($testCase->argYear, $testCase->argMonth));
        }
    }
}
