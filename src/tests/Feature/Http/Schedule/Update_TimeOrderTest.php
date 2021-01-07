<?php

namespace Tests\Feature\Http\Schedule;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\SchedulePlace;
use App\Models\ScheduleTimetable;
use App\Models\ScheduleUsage;
use App\Models\ScheduleStatus;
use App\Models\ReservationStatus;
use App\Models\Permission;
use App\Services\ReservationService;

class Update_TimeOrderTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $id;
    protected $url;
    protected $requiredPermissions;
    protected $postData;
    protected $reservationStatusList;
    protected $reservationIdSeed;

    protected $keyYmd = 'ymd';
    protected $keyBeginsAt = 'begins_at';
    protected $keyEndsAt = 'ends_at';
    protected $keyPlaceId = 'schedule_place_id';
    protected $keyUsageId = 'schedule_usage_id';
    protected $keyTimetableId = 'schedule_timetable_id';
    protected $keyScheduleStatusId = 'schedule_status_id';
    protected $keyReservationList = 'reservation_list';
    protected $keyId = 'id';
    protected $keyReservationStatusId = 'reservation_status_id';

    protected function setUp(): void
    {
        parent::setUp();
        $this->requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];
        $this->setIdAndUrl(28);
        $this->postData = [
            $this->keyYmd => '2020-12-01',
            $this->keyBeginsAt => '10:00',
            $this->keyEndsAt => '16:00',
            $this->keyPlaceId => SchedulePlace::factory()->create()->id,
            $this->keyUsageId => ScheduleUsage::factory()->create()->id,
            $this->keyTimetableId => ScheduleTimetable::factory()->create()->id,
            $this->keyScheduleStatusId => ScheduleStatus::factory()->create()->id,
            $this->keyReservationList => [],
        ];
        $this->reservationIdSeed = 29;
        $this->reservationStatusList = ReservationStatus::factory()->count(3)->create();
    }

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/schedule/update/{$id}";
    }

    protected function setPostDataScheduleTime($beginsAt, $endsAt)
    {
        $this->postData[$this->keyBeginsAt] = $beginsAt;
        $this->postData[$this->keyEndsAt] = $endsAt;
    }

    protected function addPostDataReservation($beginsAt, $endsAt)
    {
        $this->postData[$this->keyReservationList][] = [
            $this->keyId => $this->reservationIdSeed++,
            $this->keyBeginsAt => $beginsAt,
            $this->keyEndsAt => $endsAt,
            $this->keyReservationStatusId => $this->faker->randomElement($this->reservationStatusList)->id,
        ];
        return count($this->postData[$this->keyReservationList]) - 1;
    }

    protected function getReservationKey($index, $key)
    {
        $keyReservationList = $this->keyReservationList;
        return "{$keyReservationList}.{$index}.{$key}";
    }

    protected function assertSuccess()
    {
        $this->mock(ReservationService::class, function ($mock) {
            $mock->shouldReceive('updateSchedule')
                ->once()
                ->with($this->id, $this->postData)
                ->andReturn($this->id);
        });
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->id,
                ],
            ]);
    }

    protected function assertTimeOrderError($keyList)
    {
        $errors = [];
        foreach ($keyList as $key) {
            $errors[$key] = [__('validation.time_order')];
        }
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(422);
        $this->assertResponseContent($response, 'errors', $errors);
    }

    public function test_R1_R1begin_lt_Sbegin()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('09:45', '16:00');
        $this->assertTimeOrderError([
            $this->keyBeginsAt,
            $this->getReservationKey(0, $this->keyBeginsAt),
        ]);
    }

    public function test_R1_R1begin_gt_Sbegin()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:15', '16:00');
        $this->assertTimeOrderError([
            $this->keyBeginsAt,
            $this->getReservationKey(0, $this->keyBeginsAt),
        ]);
    }

    public function test_R1_R1end_lt_Send()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '15:45');
        $this->assertTimeOrderError([
            $this->keyEndsAt,
            $this->getReservationKey(0, $this->keyEndsAt),
        ]);
    }

    public function test_R1_R1end_gt_Send()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '16:15');
        $this->assertTimeOrderError([
            $this->keyEndsAt,
            $this->getReservationKey(0, $this->keyEndsAt),
        ]);
    }

    public function test_R2_R1begin_lt_SBegin()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('09:45', '13:00');
        $this->addPostDataReservation('13:00', '16:00');
        $this->assertTimeOrderError([
            $this->keyBeginsAt,
            $this->getReservationKey(0, $this->keyBeginsAt),
        ]);
    }

    public function test_R2_R1begin_gt_SBegin()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:15', '13:00');
        $this->addPostDataReservation('13:00', '16:00');
        $this->assertTimeOrderError([
            $this->keyBeginsAt,
            $this->getReservationKey(0, $this->keyBeginsAt),
        ]);
    }

    public function test_R2_R1end_lt_R2begin()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '12:45');
        $this->addPostDataReservation('13:00', '16:00');
        $this->assertTimeOrderError([
            $this->getReservationKey(0, $this->keyEndsAt),
            $this->getReservationKey(1, $this->keyBeginsAt),
        ]);
    }

    public function test_R2_R1end_gt_R2begin()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '13:00');
        $this->addPostDataReservation('12:45', '16:00');
        $this->assertTimeOrderError([
            $this->getReservationKey(0, $this->keyEndsAt),
            $this->getReservationKey(1, $this->keyBeginsAt),
        ]);
    }

    public function test_R2_R2end_lt_Send()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '13:00');
        $this->addPostDataReservation('13:00', '15:45');
        $this->assertTimeOrderError([
            $this->getReservationKey(1, $this->keyEndsAt),
            $this->keyEndsAt,
        ]);
    }

    public function test_R2_R2end_gt_Send()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '13:00');
        $this->addPostDataReservation('13:00', '16:15');
        $this->assertTimeOrderError([
            $this->getReservationKey(1, $this->keyEndsAt),
            $this->keyEndsAt,
        ]);
    }

    public function test_R1_SBegin_gt_SEnd()
    {
        $this->setPostDataScheduleTime('10:00', '09:45');
        $this->addPostDataReservation('10:00', '09:45');
        $this->assertTimeOrderError([
            $this->keyBeginsAt,
            $this->keyEndsAt,
            $this->getReservationKey(0, $this->keyBeginsAt),
            $this->getReservationKey(0, $this->keyEndsAt),
        ]);
    }

    public function test_R1_SBegin_eq_SEnd()
    {
        $this->setPostDataScheduleTime('10:00', '10:00');
        $this->addPostDataReservation('10:00', '10:00');
        $this->assertTimeOrderError([
            $this->keyBeginsAt,
            $this->keyEndsAt,
            $this->getReservationKey(0, $this->keyBeginsAt),
            $this->getReservationKey(0, $this->keyEndsAt),
        ]);
    }

    public function test_R2_R1begin_gt_R1end()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '09:45');
        $this->addPostDataReservation('09:45', '16:00');
        $this->assertTimeOrderError([
            $this->getReservationKey(0, $this->keyBeginsAt),
            $this->getReservationKey(0, $this->keyEndsAt),
        ]);
    }

    public function test_R2_R1begin_eq_R1end()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '10:00');
        $this->addPostDataReservation('10:00', '16:00');
        $this->assertTimeOrderError([
            $this->getReservationKey(0, $this->keyBeginsAt),
            $this->getReservationKey(0, $this->keyEndsAt),
        ]);
    }

    public function test_R2_R2begin_gt_R2end()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '16:15');
        $this->addPostDataReservation('16:15', '16:00');
        $this->assertTimeOrderError([
            $this->getReservationKey(1, $this->keyBeginsAt),
            $this->getReservationKey(1, $this->keyEndsAt),
        ]);
    }

    public function test_R2_R2begin_eq_R2end()
    {
        $this->setPostDataScheduleTime('10:00', '16:00');
        $this->addPostDataReservation('10:00', '16:00');
        $this->addPostDataReservation('16:00', '16:00');
        $this->assertTimeOrderError([
            $this->getReservationKey(1, $this->keyBeginsAt),
            $this->getReservationKey(1, $this->keyEndsAt),
        ]);
    }
}
