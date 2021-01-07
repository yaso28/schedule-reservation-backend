<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\ReservationService;
use App\Services\FormatService;
use App\Services\AdjustReservationStatusService;
use App\Repositories\ScheduleReservationRepository;
use App\Models\Reservation;
use stdClass;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use App\Exceptions\MyException;
use Hamcrest\Matchers;

class ReservationServiceTest extends TestCase
{
    protected $mockRecord;
    protected $mockId;
    protected $mockIdList;
    protected $mockIdCollection;
    protected $mockAttributes;
    protected $mockYmdCollection;

    protected $clmId = 'id';
    protected $clmYmd = 'ymd';
    protected $clmBeginsAt = 'begins_at';
    protected $clmEndsAt = 'ends_at';
    protected $clmPlaceId = 'schedule_place_id';
    protected $clmUsageId = 'schedule_usage_id';
    protected $clmTimetableId = 'schedule_timetable_id';
    protected $clmReservationStatusId = 'reservation_status_id';
    protected $clmScheduleStatusId = 'schedule_status_id';
    protected $clmScheduleId = 'schedule_id';
    protected $keySplitsAt = 'splits_at';
    protected $keyReservationToUpdate = 'reservation_to_update';
    protected $keyErrorMessages = 'error_messages';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRecord = new stdClass;
        $this->mockId = 3;
        $this->mockIdList = [1, 4, 2];
        $this->mockIdCollection = collect([1, 2, 3]);
        $this->mockAttributes = ['aaa' => 'bbb', 'cc' => 3,];
        $this->mockYmdCollection = collect(['2020-11-29', '2020-12-04', '2021-01-12']);
    }

    protected function getService()
    {
        return resolve(ReservationService::class);
    }

    protected function makeReservationRecord($attributes = [])
    {
        $getValue = fn ($key, $default) => array_key_exists($key, $attributes) ? $attributes[$key] : $default;
        $record = Reservation::factory()->make([
            $this->clmScheduleId => $getValue($this->clmScheduleId, 1),
            $this->clmBeginsAt => $getValue($this->clmBeginsAt, '10:00'),
            $this->clmEndsAt => $getValue($this->clmEndsAt, '16:00'),
            $this->clmReservationStatusId => $getValue($this->clmReservationStatusId, 1),
        ]);
        $record->id = $getValue($this->clmId, 1);
        return $record;
    }

    public function testGetMonthList_WithNoArgs()
    {
        $this->mock(ScheduleReservationRepository::class, function ($mock) {
            $today = Carbon::today();
            $mock->shouldReceive('selectMonthList')
                ->once()
                ->with($today->year, $today->month)
                ->andReturn($this->mockIdCollection);
        });

        $this->assertEquals(
            $this->mockIdCollection,
            $this->getService()->getMonthList()
        );
    }

    public function testGetMonthList_WithYearOnly()
    {
        $this->mock(ScheduleReservationRepository::class, function ($mock) {
            $today = Carbon::today();
            $mock->shouldReceive('selectMonthList')
                ->once()
                ->with($today->year, $today->month)
                ->andReturn($this->mockIdCollection);
        });

        $this->assertEquals(
            $this->mockIdCollection,
            $this->getService()->getMonthList('2020', null)
        );
    }

    public function testGetMonthList_WithMonthOnly()
    {
        $this->mock(ScheduleReservationRepository::class, function ($mock) {
            $today = Carbon::today();
            $mock->shouldReceive('selectMonthList')
                ->once()
                ->with($today->year, $today->month)
                ->andReturn($this->mockIdCollection);
        });

        $this->assertEquals(
            $this->mockIdCollection,
            $this->getService()->getMonthList(null, '8')
        );
    }

    public function testGetMonthList_WithYearAndMonth()
    {
        $year = '2020';
        $month = '8';

        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($year, $month) {
            $mock->shouldReceive('selectMonthList')
                ->once()
                ->with(intval($year), intval($month))
                ->andReturn($this->mockIdCollection);
        });

        $this->assertEquals(
            $this->mockIdCollection,
            $this->getService()->getMonthList($year, $month)
        );
    }

    public function testGetMonth()
    {
        $this->mock(ScheduleReservationRepository::class, function ($mock) {
            $mock->shouldReceive('selectMonth')
                ->once()
                ->with($this->mockId)
                ->andReturn($this->mockRecord);
        });

        $this->assertEquals(
            $this->mockRecord,
            $this->getService()->getMonth($this->mockId)
        );
    }

    public function testGetScheduleList_WithTo()
    {
        $from = '2020-11-01';
        $to = '2020-11-30';

        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($from, $to) {
            $mock->shouldReceive('selectScheduleList')
                ->once()
                ->with(false, $from, $to)
                ->andReturn($this->mockIdCollection);
        });

        $this->assertEquals(
            $this->mockIdCollection,
            $this->getService()->getScheduleList(false, $from, $to)
        );
    }

    public function testGetScheduleList_PublicOnly()
    {
        $this->mock(ScheduleReservationRepository::class, function ($mock) {
            $mock->shouldReceive('selectScheduleList')
                ->once()
                ->with(true, Carbon::today()->format(FormatService::DATE_FORMAT), null)
                ->andReturn($this->mockIdCollection);
        });

        $this->assertEquals(
            $this->mockIdCollection,
            $this->getService()->getScheduleList(true)
        );
    }

    public function testGetSchedule()
    {
        $this->mock(ScheduleReservationRepository::class, function ($mock) {
            $mock->shouldReceive('selectSchedule')
                ->once()
                ->with($this->mockId)
                ->andReturn($this->mockRecord);
        });

        $this->assertEquals(
            $this->mockRecord,
            $this->getService()->getSchedule($this->mockId)
        );
    }

    public function testAddScheduleList()
    {
        $ymdList = ['2020-11-29', '2020-12-15', '2021-01-03'];
        $scheduleAttributes = [
            $this->clmBeginsAt => '10:00',
            $this->clmEndsAt => '16:00',
            $this->clmPlaceId => 2,
            $this->clmUsageId => 1,
            $this->clmTimetableId => 3,
            $this->clmReservationStatusId => 6,
            $this->clmScheduleStatusId => 5,
        ];
        $argAttributes = Arr::add($scheduleAttributes, 'ymd_list', [
            $ymdList[2], $ymdList[0], $ymdList[1], $ymdList[2], $ymdList[1],
        ]);
        $scheduleAttributesList = array_map(
            fn ($ymd) => Arr::add($scheduleAttributes, $this->clmYmd, $ymd),
            $ymdList
        );
        $scheduleIdList = [54, 55, 56];
        $reservationAttributesList = array_map(
            fn ($scheduleId) => [
                $this->clmScheduleId => $scheduleId,
                $this->clmBeginsAt => $scheduleAttributes[$this->clmBeginsAt],
                $this->clmEndsAt => $scheduleAttributes[$this->clmEndsAt],
                $this->clmReservationStatusId => $scheduleAttributes[$this->clmReservationStatusId],
            ],
            $scheduleIdList
        );

        $indexObj = new stdClass;
        $indexObj->scheduleIndex = 0;
        $indexObj->reservationIndex = 0;
        $this->mock(
            ScheduleReservationRepository::class,
            function ($mock) use ($scheduleAttributesList, $scheduleIdList, $reservationAttributesList, $indexObj) {
                $count = count($scheduleAttributesList);
                $mock->shouldReceive('insertSchedule')
                    ->times($count)
                    ->andReturnUsing(function ($arg) use ($scheduleAttributesList, $scheduleIdList, $indexObj) {
                        $index = $indexObj->scheduleIndex++;
                        $this->assertEquals($scheduleAttributesList[$index], $arg);
                        return $scheduleIdList[$index];
                    });
                $mock->shouldReceive('insertReservation')
                    ->times($count)
                    ->andReturnUsing(function ($arg) use ($reservationAttributesList, $indexObj) {
                        $index = $indexObj->reservationIndex++;
                        $this->assertEquals($reservationAttributesList[$index], $arg);
                    });
            }
        );
        $this->mock(AdjustReservationStatusService::class, function ($mock) use ($ymdList) {
            $mock->shouldReceive('adjustMonthViaYmdList')
                ->once()
                ->with(Matchers::equalTo(collect($ymdList)));
        });

        $this->getService()->addScheduleList($argAttributes);
    }

    protected function assertUpdateSchedule($inputReservationAttributesList, $dbReservationIdList, $isSuccess, $includesYmd = true)
    {
        $oldYmd = '2020-12-02';
        $newYmd = '2020-12-03';
        $scheduleAttributes = [
            $this->clmYmd => '2020-12-03',
            $this->clmBeginsAt => '10:00',
            $this->clmEndsAt => '16:00',
            $this->clmPlaceId => 2,
            $this->clmUsageId => 1,
            $this->clmTimetableId => 3,
            $this->clmScheduleStatusId => 5,
        ];
        if ($includesYmd) {
            $scheduleAttributes[$this->clmYmd] = $newYmd;
        }
        $argAttributes = Arr::add($scheduleAttributes, 'reservation_list', $inputReservationAttributesList);
        $dbReservationRecordList = collect($dbReservationIdList)
            ->map(fn ($id) => $this->makeReservationRecord([$this->clmId => $id]));

        $this->mock(
            ScheduleReservationRepository::class,
            function ($mock) use ($oldYmd, $dbReservationRecordList, $scheduleAttributes, $inputReservationAttributesList, $isSuccess) {
                $mock->shouldReceive('selectScheduleYmdListViaScheduleIdList')
                    ->once()
                    ->with(Matchers::equalTo([$this->mockId]))
                    ->andReturn(collect($oldYmd));
                $mock->shouldReceive('selectReservationListViaScheduleId')
                    ->once()
                    ->with($this->mockId)
                    ->andReturn($dbReservationRecordList);

                if ($isSuccess) {
                    $mock->shouldReceive('updateSchedule')
                        ->once()
                        ->with($this->mockId, Matchers::equalTo($scheduleAttributes));
                    $indexObj = new stdClass;
                    $indexObj->index = 0;
                    $mock->shouldReceive('updateReservation')
                        ->times(count($inputReservationAttributesList))
                        ->andReturnUsing(function ($arg1, $args) use ($inputReservationAttributesList, $indexObj) {
                            $reservationAttributes = $inputReservationAttributesList[$indexObj->index++];
                            $this->assertEquals($reservationAttributes[$this->clmId], $arg1);
                            $this->assertEquals(Arr::except($reservationAttributes, $this->clmId), $args);
                        });
                } else {
                    $mock->shouldNotReceive('updateSchedule');
                    $mock->shouldNotReceive('updateReservation');
                }
            }
        );
        $this->mock(
            AdjustReservationStatusService::class,
            function ($mock) use ($isSuccess, $includesYmd, $oldYmd, $newYmd) {
                if ($isSuccess) {
                    $mock->shouldReceive('adjustScheduleViaScheduleId')
                        ->once()
                        ->with($this->mockId);
                    $mock->shouldReceive('adjustMonthViaYmdList')
                        ->once()
                        ->with(Matchers::equalTo($includesYmd ? collect([$oldYmd, $newYmd]) : collect($oldYmd)));
                } else {
                    $mock->shouldNotReceive('adjustScheduleViaScheduleId');
                    $mock->shouldNotReceive('adjustMonthViaYmdList');
                }
            }
        );

        if ($isSuccess) {
            $actualReturn = $this->getService()->updateSchedule($this->mockId, $argAttributes);
            $this->assertEquals($this->mockId, $actualReturn);
        } else {
            try {
                $this->getService()->updateSchedule($this->mockId, $argAttributes);
            } catch (MyException $e) {
                $this->assertNull($e->getCustomMessage());
            }
        }
    }

    public function testUpdateSchedule_ReservationInputLessThanDb()
    {
        $this->assertUpdateSchedule(
            [
                [$this->clmId => 64, $this->clmBeginsAt => '10:00', $this->clmEndsAt => '16:00', $this->clmReservationStatusId => 2],
            ],
            [64, 65],
            false
        );
    }

    public function testUpdateSchedule_ReservationInputGreaterThanDb()
    {
        $this->assertUpdateSchedule(
            [
                [$this->clmId => 64, $this->clmBeginsAt => '10:00', $this->clmEndsAt => '13:00', $this->clmReservationStatusId => 2],
                [$this->clmId => 65, $this->clmBeginsAt => '13:00', $this->clmEndsAt => '16:00', $this->clmReservationStatusId => 3],
            ],
            [64],
            false
        );
    }

    public function testUpdateSchedule_ReservationSingle_IncludesYmd()
    {
        $this->assertUpdateSchedule(
            [
                [$this->clmId => 64, $this->clmBeginsAt => '10:00', $this->clmEndsAt => '16:00', $this->clmReservationStatusId => 2],
            ],
            [64],
            true
        );
    }

    public function testUpdateSchedule_ReservationSplit_IncludesYmd()
    {
        $this->assertUpdateSchedule(
            [
                [$this->clmId => 64, $this->clmBeginsAt => '10:00', $this->clmEndsAt => '13:00', $this->clmReservationStatusId => 2],
                [$this->clmId => 65, $this->clmBeginsAt => '13:00', $this->clmEndsAt => '16:00', $this->clmReservationStatusId => 3],
            ],
            [64, 65],
            true
        );
    }

    protected function assertBulkChangeSchedule($includesYmd)
    {
        $newYmd = '2020-12-04';
        $attributes = $includesYmd ? Arr::add($this->mockAttributes, 'ymd', $newYmd) : $this->mockAttributes;
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($attributes) {
            $mock->shouldReceive('selectScheduleYmdListViaScheduleIdList')
                ->once($this->mockIdList)
                ->andReturn($this->mockYmdCollection);
            $mock->shouldReceive('bulkUpdateSchedule')
                ->once()
                ->with($this->mockIdList, $attributes);
        });
        $this->mock(AdjustReservationStatusService::class, function ($mock) use ($includesYmd, $newYmd) {
            $mock->shouldReceive('adjustScheduleViaScheduleIdList')
                ->once()
                ->with($this->mockIdList);
            $mock->shouldReceive('adjustMonthViaYmdList')
                ->once()
                ->with(Matchers::equalTo($includesYmd ? $this->mockYmdCollection->add($newYmd) : $this->mockYmdCollection));
        });

        $this->getService()->bulkChangeSchedule($this->mockIdList, $attributes);
    }

    public function testBulkChangeSchedule_IncludesYmd()
    {
        $this->assertBulkChangeSchedule(true);
    }

    public function testBulkChangeSchedule_NotIncludesYmd()
    {
        $this->assertBulkChangeSchedule(false);
    }

    public function testGetReservationList_WithFromTo()
    {
        $from = '2020-11-01';
        $to = '2020-11-30';

        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($from, $to) {
            $mock->shouldReceive('selectReservationList')
                ->once()
                ->with($from, $to)
                ->andReturn($this->mockIdCollection);
        });

        $this->assertEquals(
            $this->mockIdCollection,
            $this->getService()->getReservationList($from, $to)
        );
    }

    public function testGetReservationList_WithNoArgs()
    {
        $this->mock(ScheduleReservationRepository::class, function ($mock) {
            $mock->shouldReceive('selectReservationList')
                ->once()
                ->with(Carbon::today()->format(FormatService::DATE_FORMAT), null)
                ->andReturn($this->mockIdCollection);
        });

        $this->assertEquals(
            $this->mockIdCollection,
            $this->getService()->getReservationList()
        );
    }

    public function testGetReservationListForSchedule()
    {
        $this->mock(ScheduleReservationRepository::class, function ($mock) {
            $mock->shouldReceive('selectReservationListViaScheduleId')
                ->once()
                ->with($this->mockId)
                ->andReturn($this->mockIdCollection);
        });

        $this->assertEquals(
            $this->mockIdCollection,
            $this->getService()->getReservationListForSchedule($this->mockId)
        );
    }

    protected function makeTestDataForSplitReservation($beginsAt, $endsAt, $splitsAt)
    {
        $scheduleId = 23;
        $reservationStatusId = 5;
        $reservationToUpdate = $this->makeReservationRecord([
            $this->clmId => $this->mockId,
            $this->clmScheduleId => $scheduleId,
            $this->clmBeginsAt => $beginsAt,
            $this->clmEndsAt => $endsAt,
            $this->clmReservationStatusId => $reservationStatusId,
        ]);
        return [
            $this->clmScheduleId => $scheduleId,
            $this->clmBeginsAt => $beginsAt,
            $this->clmEndsAt => $endsAt,
            $this->clmReservationStatusId => $reservationStatusId,
            $this->keySplitsAt => $splitsAt,
            $this->keyReservationToUpdate => $reservationToUpdate,
            $this->keyErrorMessages => [
                $this->keySplitsAt => [__('validation.time_order')],
            ],
        ];
    }

    public function testSplitReservation_SplitsAtLessThanBeginsAt()
    {
        $testData = $this->makeTestDataForSplitReservation('10:00', '16:00', '09:45');
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($testData) {
            $mock->shouldReceive('selectReservation')
                ->once()
                ->with($this->mockId)
                ->andReturn($testData[$this->keyReservationToUpdate]);
            $mock->shouldNotReceive('saveEntityList');
        });

        try {
            $this->getService()->splitReservation($this->mockId, $testData[$this->keySplitsAt]);
        } catch (ValidationException $e) {
            $this->assertEquals($testData[$this->keyErrorMessages], $e->errors());
        }
    }

    public function testSplitReservation_SplitsAtEqualToBeginsAt()
    {
        $testData = $this->makeTestDataForSplitReservation('10:00', '16:00', '10:00');
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($testData) {
            $mock->shouldReceive('selectReservation')
                ->once()
                ->with($this->mockId)
                ->andReturn($testData[$this->keyReservationToUpdate]);
            $mock->shouldNotReceive('saveEntityList');
        });

        try {
            $this->getService()->splitReservation($this->mockId, $testData[$this->keySplitsAt]);
        } catch (ValidationException $e) {
            $this->assertEquals($testData[$this->keyErrorMessages], $e->errors());
        }
    }

    public function testSplitReservation_SplitsAtEqualToEndsAt()
    {
        $testData = $this->makeTestDataForSplitReservation('10:00', '16:00', '16:00');
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($testData) {
            $mock->shouldReceive('selectReservation')
                ->once()
                ->with($this->mockId)
                ->andReturn($testData[$this->keyReservationToUpdate]);
            $mock->shouldNotReceive('saveEntityList');
        });

        try {
            $this->getService()->splitReservation($this->mockId, $testData[$this->keySplitsAt]);
        } catch (ValidationException $e) {
            $this->assertEquals($testData[$this->keyErrorMessages], $e->errors());
        }
    }

    public function testSplitReservation_SplitsAtGreaterThanEndsAt()
    {
        $testData = $this->makeTestDataForSplitReservation('10:00', '16:00', '16:15');
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($testData) {
            $mock->shouldReceive('selectReservation')
                ->once()
                ->with($this->mockId)
                ->andReturn($testData[$this->keyReservationToUpdate]);
            $mock->shouldNotReceive('saveEntityList');
        });

        try {
            $this->getService()->splitReservation($this->mockId, $testData[$this->keySplitsAt]);
        } catch (ValidationException $e) {
            $this->assertEquals($testData[$this->keyErrorMessages], $e->errors());
        }
    }

    public function testSplitReservation_Success()
    {
        $testData = $this->makeTestDataForSplitReservation('10:00', '16:00', '13:00');
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($testData) {
            $mock->shouldReceive('selectReservation')
                ->once()
                ->with($this->mockId)
                ->andReturn($testData[$this->keyReservationToUpdate]);
            $mock->shouldReceive('saveEntityList')
                ->once()
                ->andReturnUsing(function ($reservationList) use ($testData) {
                    $this->assertEquals(2, count($reservationList));
                    $this->assertSame($testData[$this->keyReservationToUpdate], $reservationList[0]);
                    $this->assertEquals($this->mockId, $reservationList[0]->id);
                    $this->assertEquals($testData[$this->clmScheduleId], $reservationList[0][$this->clmScheduleId]);
                    $this->assertEquals($testData[$this->clmBeginsAt], $reservationList[0][$this->clmBeginsAt]);
                    $this->assertEquals($testData[$this->keySplitsAt], $reservationList[0][$this->clmEndsAt]);
                    $this->assertEquals($testData[$this->clmReservationStatusId], $reservationList[0][$this->clmReservationStatusId]);
                    $this->assertNull($reservationList[1]->id);
                    $this->assertEquals($testData[$this->clmScheduleId], $reservationList[1][$this->clmScheduleId]);
                    $this->assertEquals($testData[$this->keySplitsAt], $reservationList[1][$this->clmBeginsAt]);
                    $this->assertEquals($testData[$this->clmEndsAt], $reservationList[1][$this->clmEndsAt]);
                    $this->assertEquals($testData[$this->clmReservationStatusId], $reservationList[1][$this->clmReservationStatusId]);
                });
        });

        $result = $this->getService()->splitReservation($this->mockId, $testData[$this->keySplitsAt]);
        $this->assertEquals($testData[$this->clmScheduleId], $result);
    }

    public function testBulkChangeReservation()
    {
        $this->mock(ScheduleReservationRepository::class, function ($mock) {
            $mock->shouldReceive('selectScheduleIdListViaReservationIdList')
                ->once()
                ->with($this->mockIdList)
                ->andReturn($this->mockIdCollection);
            $mock->shouldReceive('selectScheduleYmdListViaScheduleIdList')
                ->once()
                ->with($this->mockIdCollection)
                ->andReturn($this->mockYmdCollection);
            $mock->shouldReceive('bulkUpdateReservation')
                ->once()
                ->with($this->mockIdList, $this->mockAttributes);
        });
        $this->mock(AdjustReservationStatusService::class, function ($mock) {
            $mock->shouldReceive('adjustScheduleViaScheduleIdList')
                ->once()
                ->with($this->mockIdCollection);
            $mock->shouldReceive('adjustMonthViaYmdList')
                ->once()
                ->with($this->mockYmdCollection);
        });

        $this->getService()->bulkChangeReservation($this->mockIdList, $this->mockAttributes);
    }
}
