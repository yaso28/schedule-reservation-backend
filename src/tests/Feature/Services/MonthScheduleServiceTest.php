<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Utilities\DataHelper;
use Illuminate\Support\Collection;
use App\Services\MonthScheduleService;
use App\Repositories\ScheduleReservationRepository;
use App\Services\ScheduleMasterService;
use App\Services\SettingService;
use App\Services\FormatService;
use App\NotificationServices\SendService;
use App\Models\Month;
use App\Models\Schedule;
use App\Models\SchedulePlace;
use App\Models\ScheduleUsage;
use App\Models\ScheduleStatus;
use App\Models\Category;
use App\Models\Setting;

class MonthScheduleServiceTest extends TestCase
{
    use WithFaker;

    protected $scheduleIdSeed;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduleIdSeed = 1;
    }


    protected function getService()
    {
        return resolve(MonthScheduleService::class);
    }

    protected function mockService($closure)
    {
        $this->partialMockWithArgs(
            MonthScheduleService::class,
            [
                resolve(ScheduleReservationRepository::class),
                resolve(ScheduleMasterService::class),
                resolve(SettingService::class),
                resolve(FormatService::class),
                resolve(SendService::class),
            ],
            $closure
        );
    }

    protected function makeSettingRecord($categoryName, $keyName, $value)
    {
        return Setting::factory()->make([
            'category_name' => $categoryName,
            'key_name' => $keyName,
            'value' => $value,
        ]);
    }

    protected function makeUsageRecord($id = -1, $isPublic = true)
    {
        $record = ScheduleUsage::factory()->make([
            'is_public' => $isPublic,
            'reservation_organization_id' => -1,
        ]);
        $record->id = $id;
        return $record;
    }

    protected function makeScheduleStatusRecord($id = -1, $bulkChangeMode = ScheduleStatus::BULK_CHANGE_NONE)
    {
        $record = ScheduleStatus::factory()->make([
            'bulk_change_mode' => $bulkChangeMode,
        ]);
        $record->id = $id;
        return $record;
    }

    protected function makeMonthRecord($id = -1, $scheduleStatusId = -1)
    {
        $record = Month::factory()->make([
            'reservation_status_id' => -1,
            'schedule_status_id' => $scheduleStatusId,
        ]);
        $record->id = $id;
        return $record;
    }

    protected function makeScheduleRecord($scheduleStatus = null, $usage = null, $place = null)
    {
        $record = Schedule::factory()->make([
            'schedule_place_id' => $place ? $place->id ?? -1 : -1,
            'schedule_usage_id' => $usage ? $usage->id ?? -1 : -1,
            'schedule_timetable_id' => -1,
            'reservation_status_id' => -1,
            'schedule_status_id' => $scheduleStatus ? $scheduleStatus->id ?? -1 : -1,
        ]);
        $record->id = $this->scheduleIdSeed++;
        $record->schedule_place = $place;
        $record->schedule_usage = $usage;
        $record->schedule_status = $scheduleStatus;
        return $record;
    }

    public function testPrepareSendInfo()
    {
        $monthId = 3;
        $month = $this->makeMonthRecord($monthId);
        $expected = [
            'month' => $month,
            'mail_to' => $this->faker->safeEmail,
            'subject' => DataHelper::randomText(),
            'message' => $this->faker->realText(500),
        ];
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($monthId, $month) {
            $mock->shouldReceive('selectMonth')
                ->once()
                ->with($monthId)
                ->andReturn($month);
        });
        $this->mockService(function ($mock) use ($expected) {
            $mock->shouldReceive('prepareMailTo')
                ->once()
                ->withNoArgs()
                ->andReturn($expected['mail_to']);
            $mock->shouldReceive('prepareSubject')
                ->once()
                ->with($expected['month'])
                ->andReturn($expected['subject']);
            $mock->shouldReceive('prepareMessage')
                ->once()
                ->with($expected['month'])
                ->andReturn($expected['message']);
        });
        $this->assertEquals($expected, $this->getService()->prepareSendInfo($monthId));
    }

    /*
    public function testPrepareMailTo()
    {
        $expected = $this->faker->safeEmail;
        $this->mock(SettingService::class, function ($mock) use ($expected) {
            $mock->shouldReceive('get')
                ->once()
                ->with(Category::RESERVATION, Setting::KEY_MAIL_TO)
                ->andReturn($this->makeSettingRecord(Category::RESERVATION, Setting::KEY_MAIL_TO, $expected));
        });
        $this->assertEquals($expected, $this->getService()->prepareMailTo());
    }
    */

    public function testPrepareSubject()
    {
        $month = $this->makeMonthRecord();
        $monthName = $month->name;
        $template = '{month_name}の練習予定';
        $expected = "${monthName}の練習予定";
        $this->mock(SettingService::class, function ($mock) use ($template) {
            $mock->shouldReceive('get')
                ->once()
                ->with(Category::RESERVATION, Setting::KEY_MAIL_SUBJECT)
                ->andReturn($this->makeSettingRecord(Category::RESERVATION, Setting::KEY_MAIL_SUBJECT, $template));
        });
        $this->assertEquals($expected, $this->getService()->prepareSubject($month));
    }

    public function testPrepareMessage()
    {
        $month = $this->makeMonthRecord();
        $messageBegin = $this->faker->realText(50);
        $messageSchedulesCollection = Collection::times(20, fn () => DataHelper::randomText(30));
        $messageNotesCollection = Collection::times(2, fn () => $this->faker->realText(40));
        $messageEnd = $this->faker->realText(50);
        $empty = '';
        $this->mockService(function ($mock) use ($month, $messageBegin, $messageSchedulesCollection, $messageNotesCollection, $messageEnd) {
            $mock->shouldReceive('prepareMessageBegin')
                ->once()
                ->with($month)
                ->andReturn($messageBegin);
            $mock->shouldReceive('prepareMessageScheduleListLineCollection')
                ->once()
                ->with($month)
                ->andReturn($messageSchedulesCollection);
            $mock->shouldReceive('prepareMessageNotesLineCollection')
                ->once()
                ->withNoArgs()
                ->andReturn($messageNotesCollection);
            $mock->shouldReceive('prepareMessageEnd')
                ->once()
                ->withNoArgs()
                ->andReturn($messageEnd);
        });
        $expected = collect()
            ->add($messageBegin)
            ->add($empty)
            ->concat($messageSchedulesCollection)
            ->concat($messageNotesCollection)
            ->add($empty)
            ->add($messageEnd)
            ->add($empty)
            ->join("\n");
        $this->assertEquals($expected, $this->getService()->prepareMessage($month));
    }

    public function testPrepareMessageBegin()
    {
        $month = $this->makeMonthRecord();
        $monthName = $month->name;
        $preceding = $this->faker->realText(20);
        $subsequent = $this->faker->realText(20);
        $template = "${preceding}\n{month_name}${subsequent}";
        $expected = "${preceding}\n${monthName}${subsequent}";
        $this->mock(SettingService::class, function ($mock) use ($template) {
            $mock->shouldReceive('get')
                ->once()
                ->with(Category::RESERVATION, Setting::KEY_MAIL_MESSAGE_BEGIN)
                ->andReturn($this->makeSettingRecord(Category::RESERVATION, Setting::KEY_MAIL_MESSAGE_BEGIN, $template));
        });
        $this->assertEquals($expected, $this->getService()->prepareMessageBegin($month));
    }

    protected function mockPrepareMessageSchedule($schedule)
    {
        $id = $schedule->id;
        return "Schedule.${id}: 12/20(日) 10:00-13:00 体育館";
    }

    public function testPrepareMessageScheduleListLineCollection_Normal()
    {
        $month = $this->makeMonthRecord();
        $usageList = collect([
            $this->makeUsageRecord(1, true),
            $this->makeUsageRecord(2, true),
            $this->makeUsageRecord(3, false),
            $this->makeUsageRecord(4, true),
        ]);
        $publicUsageList = collect([$usageList[0], $usageList[1], $usageList[3]]);
        $statusList = collect([
            $this->makeScheduleStatusRecord(1, ScheduleStatus::BULK_CHANGE_FROM),
            $this->makeScheduleStatusRecord(2, ScheduleStatus::BULK_CHANGE_TO),
            $this->makeScheduleStatusRecord(3, ScheduleStatus::BULK_CHANGE_NONE),
        ]);
        $targetstatusList = collect([$statusList[0], $statusList[1]]);
        $scheduleList = Collection::times(20, fn () => $this->makeScheduleRecord(
            $this->faker->randomElement($statusList),
            $this->faker->randomElement($usageList)
        ));
        foreach ($usageList as $usage) {
            $scheduleList->add($this->makeScheduleRecord($statusList[0], $usage));
        }

        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($month, $scheduleList) {
            $mock->shouldReceive('selectScheduleList')
                ->once()
                ->with(true, $month->first_day, $month->last_day)
                ->andReturn($scheduleList);
        });
        $this->mock(ScheduleMasterService::class, function ($mock) use ($usageList) {
            $mock->shouldReceive('getScheduleUsageList')
                ->once()
                ->withNoArgs()
                ->andReturn($usageList);
        });
        $this->mockService(function ($mock) {
            $mock->shouldReceive('prepareMessageSchedule')
                ->andReturnUsing(fn ($schedule) => $this->mockPrepareMessageSchedule($schedule));
        });

        $expected = collect();
        foreach ($publicUsageList as $usage) {
            $expected->add(__('mail.header.prefix') . $usage->name . __('mail.header.suffix'));
            foreach ($scheduleList->filter(fn ($schedule) => $schedule->schedule_usage == $usage && $targetstatusList->contains($schedule->schedule_status)) as $schedule) {
                $expected->add($this->mockPrepareMessageSchedule($schedule));
            }
            $expected->add('');
        }
        $this->assertEquals($expected, $this->getService()->prepareMessageScheduleListLineCollection($month));
    }

    public function testPrepareMessageScheduleListLineCollection_ExistsUsageWithoutSchedules()
    {
        $month = $this->makeMonthRecord();
        $usageList = collect([
            $this->makeUsageRecord(1, true),
            $this->makeUsageRecord(2, true),
            $this->makeUsageRecord(3, false),
            $this->makeUsageRecord(4, true),
        ]);
        $usageWithSchedulesList = collect([$usageList[0], $usageList[2], $usageList[3]]);
        $usageWithoutSchedules = $usageList[1];
        $publicUsageList = collect([$usageList[0], $usageList[1], $usageList[3]]);
        $statusList = collect([
            $this->makeScheduleStatusRecord(1, ScheduleStatus::BULK_CHANGE_FROM),
            $this->makeScheduleStatusRecord(2, ScheduleStatus::BULK_CHANGE_TO),
            $this->makeScheduleStatusRecord(3, ScheduleStatus::BULK_CHANGE_NONE),
        ]);
        $targetstatusList = collect([$statusList[0], $statusList[1]]);
        $scheduleList = Collection::times(20, fn () => $this->makeScheduleRecord(
            $this->faker->randomElement($statusList),
            $this->faker->randomElement($usageWithSchedulesList)
        ));
        foreach ($usageWithSchedulesList as $usage) {
            $scheduleList->add($this->makeScheduleRecord($statusList[0], $usage));
        }

        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($month, $scheduleList) {
            $mock->shouldReceive('selectScheduleList')
                ->once()
                ->with(true, $month->first_day, $month->last_day)
                ->andReturn($scheduleList);
        });
        $this->mock(ScheduleMasterService::class, function ($mock) use ($usageList) {
            $mock->shouldReceive('getScheduleUsageList')
                ->once()
                ->withNoArgs()
                ->andReturn($usageList);
        });
        $this->mockService(function ($mock) {
            $mock->shouldReceive('prepareMessageSchedule')
                ->andReturnUsing(fn ($schedule) => $this->mockPrepareMessageSchedule($schedule));
        });

        $expected = collect();
        foreach ($publicUsageList as $usage) {
            $expected->add(__('mail.header.prefix') . $usage->name . __('mail.header.suffix'));
            if ($usage == $usageWithoutSchedules) {
                $expected->add(__('mail.none'));
            } else {
                foreach ($scheduleList->filter(fn ($schedule) => $schedule->schedule_usage == $usage && $targetstatusList->contains($schedule->schedule_status)) as $schedule) {
                    $expected->add($this->mockPrepareMessageSchedule($schedule));
                }
            }
            $expected->add('');
        }
        $this->assertEquals($expected, $this->getService()->prepareMessageScheduleListLineCollection($month));
    }

    public function testPrepareMessageSchedule()
    {
        $schedule = $this->makeScheduleRecord(null, null, SchedulePlace::factory()->make());
        $formatService = resolve(FormatService::class);
        $expected =
            $formatService->dateMonthDayWeek($schedule->ymd) . ' ' .
            $formatService->time($schedule->begins_at) . '-' .
            $formatService->time($schedule->ends_at) . ' ' .
            $schedule->schedule_place->abbreviation;
        $this->assertEquals($expected, $this->getService()->prepareMessageSchedule($schedule));
    }

    public function testPrepareMessageNotesLineCollection_Normal()
    {
        $notes = $this->faker->realText(40);
        $this->mock(SettingService::class, function ($mock) use ($notes) {
            $mock->shouldReceive('get')
                ->once()
                ->with(Category::RESERVATION_PUBLIC, Setting::KEY_NOTES)
                ->andReturn($this->makeSettingRecord(Category::RESERVATION_PUBLIC, Setting::KEY_NOTES, $notes));
        });
        $this->assertEquals(
            collect([__('mail.header.prefix') . __('mail.header.notes') . __('mail.header.suffix'), $notes]),
            $this->getService()->prepareMessageNotesLineCollection()
        );
    }

    public function testPrepareMessageNotesLineCollection_NotesEmpty()
    {
        $this->mock(SettingService::class, function ($mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with(Category::RESERVATION_PUBLIC, Setting::KEY_NOTES)
                ->andReturn($this->makeSettingRecord(Category::RESERVATION_PUBLIC, Setting::KEY_NOTES, ''));
        });
        $this->assertEquals(
            collect([__('mail.header.prefix') . __('mail.header.notes') . __('mail.header.suffix'), __('mail.none')]),
            $this->getService()->prepareMessageNotesLineCollection()
        );
    }

    public function testPrepareMessageEnd()
    {
        $expected = $this->faker->realText(50);
        $this->mock(SettingService::class, function ($mock) use ($expected) {
            $mock->shouldReceive('get')
                ->once()
                ->with(Category::RESERVATION, Setting::KEY_MAIL_MESSAGE_END)
                ->andReturn($this->makeSettingRecord(Category::RESERVATION, Setting::KEY_MAIL_MESSAGE_END, $expected));
        });
        $this->assertEquals($expected, $this->getService()->prepareMessageEnd());
    }

    public function testSend_Normal()
    {
        $statusList = collect([
            $this->makeScheduleStatusRecord(1, ScheduleStatus::BULK_CHANGE_FROM),
            $this->makeScheduleStatusRecord(2, ScheduleStatus::BULK_CHANGE_FROM),
            $this->makeScheduleStatusRecord(3, ScheduleStatus::BULK_CHANGE_TO),
            $this->makeScheduleStatusRecord(4, ScheduleStatus::BULK_CHANGE_NONE),
            $this->makeScheduleStatusRecord(5, ScheduleStatus::BULK_CHANGE_NONE),
        ]);
        $statusBulkFromList = collect([$statusList[0], $statusList[1]]);
        $statusBulkTo = $statusList[2];
        $scheduleList = Collection::times(20, fn () => $this->makeScheduleRecord(
            $this->faker->randomElement($statusList)
        ));
        foreach ($statusList as $status) {
            $scheduleList->add($this->makeScheduleRecord($status));
        }
        $bulkScheduleList = $scheduleList->filter(fn ($schedule) => $statusBulkFromList->contains($schedule->schedule_status))->values();
        $monthId = 4;
        $month = $this->makeMonthRecord($monthId, $statusBulkFromList[0]->id);
        $sendInfo = [
            'mail_to' => $this->faker->safeEmail,
            'subject' => DataHelper::randomText(),
            'message' => $this->faker->realText(500),
        ];

        $this->mock(SendService::class, function ($mock) use ($sendInfo) {
            $mock->shouldReceive('send')
                ->once()
                ->with($sendInfo);
        });
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($monthId, $month, $scheduleList, $bulkScheduleList, $statusBulkTo) {
            $mock->shouldReceive('selectMonth')
                ->once()
                ->with($monthId)
                ->andReturn($month);
            $mock->shouldReceive('selectScheduleList')
                ->once()
                ->with(true, $month->first_day, $month->last_day)
                ->andReturn($scheduleList);
            $mock->shouldReceive('saveEntityList')
                ->once()
                ->andReturnUsing(function ($arg) use ($bulkScheduleList, $statusBulkTo) {
                    $this->assertEquals($bulkScheduleList, $arg);
                    foreach ($arg as $argSchedule) {
                        $this->assertEquals($statusBulkTo->id, $argSchedule->schedule_status_id);
                    }
                });
            $mock->shouldReceive('saveEntity')
                ->once()
                ->andReturnUsing(function ($arg) use ($month, $statusBulkTo) {
                    $this->assertSame($month, $arg);
                    $this->assertEquals($statusBulkTo->id, $arg->schedule_status_id);
                });
        });
        $this->mock(ScheduleMasterService::class, function ($mock) use ($statusBulkTo) {
            $mock->shouldReceive('getFixedScheduleStatus')
                ->once()
                ->withNoArgs()
                ->andReturn($statusBulkTo);
        });

        $this->assertEquals($monthId, $this->getService()->send($monthId, $sendInfo));
    }

    public function testSend_NoBulkTargets()
    {
        $statusList = collect([
            $this->makeScheduleStatusRecord(3, ScheduleStatus::BULK_CHANGE_TO),
            $this->makeScheduleStatusRecord(4, ScheduleStatus::BULK_CHANGE_NONE),
            $this->makeScheduleStatusRecord(5, ScheduleStatus::BULK_CHANGE_NONE),
        ]);
        $scheduleList = Collection::times(20, fn () => $this->makeScheduleRecord(
            $this->faker->randomElement($statusList)
        ));
        foreach ($statusList as $status) {
            $scheduleList->add($this->makeScheduleRecord($status));
        }
        $monthId = 4;
        $month = $this->makeMonthRecord($monthId);
        $sendInfo = [
            'mail_to' => $this->faker->safeEmail,
            'subject' => DataHelper::randomText(),
            'message' => $this->faker->realText(500),
        ];

        $this->mock(SendService::class, function ($mock) use ($sendInfo) {
            $mock->shouldReceive('send')
                ->once()
                ->with($sendInfo);
        });
        $this->mock(ScheduleReservationRepository::class, function ($mock) use ($monthId, $month, $scheduleList) {
            $mock->shouldReceive('selectMonth')
                ->once()
                ->with($monthId)
                ->andReturn($month);
            $mock->shouldReceive('selectScheduleList')
                ->once()
                ->with(true, $month->first_day, $month->last_day)
                ->andReturn($scheduleList);
            $mock->shouldNotReceive('saveEntityList');
            $mock->shouldNotReceive('saveEntity');
        });
        $this->mock(ScheduleMasterService::class, function ($mock) {
            $mock->shouldNotReceive('getFixedScheduleStatus');
        });

        $this->assertEquals($monthId, $this->getService()->send($monthId, $sendInfo));
    }
}
