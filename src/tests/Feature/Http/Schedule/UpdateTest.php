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
use App\Exceptions\MyException;

class UpdateTest extends TestCase
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
        ];
        $this->reservationIdSeed = 29;
        $this->reservationStatusList = ReservationStatus::factory()->count(3)->create();
        $this->initPostDataReservationList();
        $this->addPostDataReservation('10:00', '16:00');
    }

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/schedule/update/{$id}";
    }

    protected function initPostDataReservationList($reservationList = [])
    {
        $this->postData[$this->keyReservationList] = $reservationList;
    }

    protected function addPostDataReservation($beginsAt, $endsAt, $reservationStatusId = false, $id = false)
    {
        $this->postData[$this->keyReservationList][] = [
            $this->keyId => $id === false ? $this->reservationIdSeed++ : $id,
            $this->keyBeginsAt => $beginsAt,
            $this->keyEndsAt => $endsAt,
            $this->keyReservationStatusId => $reservationStatusId === false ? $this->faker->randomElement($this->reservationStatusList)->id : $reservationStatusId,
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

    protected function assertValidationError($errors)
    {
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(422);
        $this->assertResponseContent($response, 'errors', $errors);
    }

    public function testNoLogin()
    {
        $response = $this->postJson($this->url, $this->postData);
        $response->assertStatus(401);
    }

    public function testNoPermission()
    {
        $response = $this->actingAs($this->createUser())
            ->postJson($this->url, $this->postData);
        $response->assertStatus(403);
    }

    public function testPermissionReadOnly()
    {
        $response = $this->actingAs($this->createUser([Permission::RESERVATION_READ]))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(403);
    }

    public function testYmdEmpty()
    {
        $this->postData[$this->keyYmd] = '';
        $this->assertValidationError([
            $this->keyYmd => [
                __('validation.required')
            ]
        ]);
    }

    public function testYmdNotDate()
    {
        $this->postData[$this->keyYmd] =  'abc';
        $this->assertValidationError([
            $this->keyYmd => [
                __('validation.date_format')
            ]
        ]);
    }

    public function testBeginsAtEmpty()
    {
        $this->postData[$this->keyBeginsAt] = '';
        $this->assertValidationError([
            $this->keyBeginsAt => [
                __('validation.required')
            ]
        ]);
    }

    public function testBeginsAtNotTime()
    {
        $this->postData[$this->keyBeginsAt] = 'abc';
        $this->assertValidationError([
            $this->keyBeginsAt => [
                __('validation.date_format')
            ]
        ]);
    }

    public function testEndsAtEmpty()
    {
        $this->postData[$this->keyEndsAt] = '';
        $this->assertValidationError([
            $this->keyEndsAt => [
                __('validation.required')
            ]
        ]);
    }

    public function testEndsAtNotTime()
    {
        $this->postData[$this->keyEndsAt] = 'abc';
        $this->assertValidationError([
            $this->keyEndsAt => [
                __('validation.date_format')
            ]
        ]);
    }

    public function testPlaceIdNull()
    {
        $this->postData[$this->keyPlaceId] = null;
        $this->assertValidationError([
            $this->keyPlaceId => [
                __('validation.required')
            ]
        ]);
    }

    public function testPlaceIdNotExists()
    {
        $this->postData[$this->keyPlaceId] = SchedulePlace::max('id') + 2;
        $this->assertValidationError([
            $this->keyPlaceId => [
                __('validation.exists')
            ]
        ]);
    }

    public function testUsageIdNull()
    {
        $this->postData[$this->keyUsageId] = null;
        $this->assertValidationError([
            $this->keyUsageId => [
                __('validation.required')
            ]
        ]);
    }

    public function testUsageIdNotExists()
    {
        $this->postData[$this->keyUsageId] = ScheduleUsage::max('id') + 2;
        $this->assertValidationError([
            $this->keyUsageId => [
                __('validation.exists')
            ]
        ]);
    }

    public function testTimetableIdNull()
    {
        $this->postData[$this->keyTimetableId] = null;
        $this->assertSuccess();
    }

    public function testTimetableIdNotExists()
    {
        $this->postData[$this->keyTimetableId] = ScheduleTimetable::max('id') + 2;
        $this->assertValidationError([
            $this->keyTimetableId => [
                __('validation.exists')
            ]
        ]);
    }

    public function testScheduleStatusIdNull()
    {
        $this->postData[$this->keyScheduleStatusId] = null;
        $this->assertValidationError([
            $this->keyScheduleStatusId => [
                __('validation.required')
            ]
        ]);
    }

    public function testScheduleStatusIdNotExists()
    {
        $this->postData[$this->keyScheduleStatusId] = ScheduleStatus::max('id') + 2;
        $this->assertValidationError([
            $this->keyScheduleStatusId => [
                __('validation.exists')
            ]
        ]);
    }

    public function testReservationListNull()
    {
        $this->initPostDataReservationList(null);
        $this->assertValidationError([
            $this->keyReservationList => [
                __('validation.required')
            ]
        ]);
    }

    public function testReservationListEmptyArray()
    {
        $this->initPostDataReservationList([]);
        $this->assertValidationError([
            $this->keyReservationList => [
                __('validation.required')
            ]
        ]);
    }

    public function testReservationListNotArray()
    {
        $this->initPostDataReservationList('abc');
        $this->assertValidationError([
            $this->keyReservationList => [
                __('validation.array')
            ]
        ]);
    }

    public function testReservationListIncludesIdNull()
    {
        $this->initPostDataReservationList();
        $this->addPostDataReservation('10:00', '13:00');
        $lastIndex = $this->addPostDataReservation('13:00', '16:00', false, null);
        $this->assertValidationError([
            $this->getReservationKey($lastIndex, $this->keyId) => [
                __('validation.required')
            ]
        ]);
    }

    public function testReservationListIncludesIdNotDistinct()
    {
        $this->initPostDataReservationList();
        $this->addPostDataReservation('10:00', '12:00', false, 51);
        $this->addPostDataReservation('12:00', '14:00', false, 52);
        $this->addPostDataReservation('14:00', '16:00', false, 51);
        $this->assertValidationError([
            $this->getReservationKey(0, $this->keyId) => [
                __('validation.distinct')
            ],
            $this->getReservationKey(2, $this->keyId) => [
                __('validation.distinct')
            ],
        ]);
    }

    public function testReservationListIncludesBeginsAtEmpty()
    {
        $lastIndex = $this->addPostDataReservation('', '16:00');
        $this->assertValidationError([
            $this->getReservationKey($lastIndex, $this->keyBeginsAt) => [
                __('validation.required')
            ]
        ]);
    }

    public function testReservationListIncludesBeginsAtNotTime()
    {
        $lastIndex = $this->addPostDataReservation('abc', '16:00');
        $this->assertValidationError([
            $this->getReservationKey($lastIndex, $this->keyBeginsAt) => [
                __('validation.date_format')
            ]
        ]);
    }

    public function testReservationListIncludesEndsAtEmpty()
    {
        $lastIndex = $this->addPostDataReservation('10:00', '');
        $this->assertValidationError([
            $this->getReservationKey($lastIndex, $this->keyEndsAt) => [
                __('validation.required')
            ]
        ]);
    }

    public function testReservationListIncludesEndsAtNotTime()
    {
        $lastIndex = $this->addPostDataReservation('10:00', 'xyz');
        $this->assertValidationError([
            $this->getReservationKey($lastIndex, $this->keyEndsAt) => [
                __('validation.date_format')
            ]
        ]);
    }

    public function testReservationListIncludesReservationStatusIdNull()
    {
        $this->initPostDataReservationList();
        $this->addPostDataReservation('10:00', '13:00');
        $lastIndex = $this->addPostDataReservation('13:00', '16:00', null);
        $this->assertValidationError([
            $this->getReservationKey($lastIndex, $this->keyReservationStatusId) => [
                __('validation.required')
            ]
        ]);
    }

    public function testReservationListIncludesReservationStatusIdNotExists()
    {
        $this->initPostDataReservationList();
        $this->addPostDataReservation('10:00', '13:00');
        $lastIndex = $this->addPostDataReservation('13:00', '16:00', ReservationStatus::max('id') + 2);
        $this->assertValidationError([
            $this->getReservationKey($lastIndex, $this->keyReservationStatusId) => [
                __('validation.exists')
            ]
        ]);
    }

    public function testSuccess_ReservationSingle()
    {
        $this->assertSuccess();
    }

    public function testSuccess_ReservationSplit()
    {
        $this->initPostDataReservationList();
        $this->addPostDataReservation('10:00', '13:00');
        $this->addPostDataReservation('13:00', '16:00');
        $this->assertSuccess();
    }

    public function testServiceThrowsMyException()
    {
        $this->mock(ReservationService::class, function ($mock) {
            $mock->shouldReceive('updateSchedule')
                ->once()
                ->with($this->id, $this->postData)
                ->andThrows(new MyException);
        });
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(499);
        $this->assertResponseContent($response, 'custom_message', null);
    }
}
