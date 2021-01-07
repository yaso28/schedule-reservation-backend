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

class AddListTest extends TestCase
{
    use RefreshDatabase;

    protected $url = '/api/schedule/add-list';
    protected $requiredPermissions;
    protected $postData;
    protected $mockId = 31;

    protected $keyYmdList = 'ymd_list';
    protected $keyBeginsAt = 'begins_at';
    protected $keyEndsAt = 'ends_at';
    protected $keyPlaceId = 'schedule_place_id';
    protected $keyUsageId = 'schedule_usage_id';
    protected $keyTimetableId = 'schedule_timetable_id';
    protected $keyReservationStatusId = 'reservation_status_id';
    protected $keyScheduleStatusId = 'schedule_status_id';

    protected function setUp(): void
    {
        parent::setUp();
        $this->requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];
        $this->postData = [
            $this->keyYmdList => ['2020-12-05', '2020-12-06', '2020-12-12'],
            $this->keyBeginsAt => '10:00',
            $this->keyEndsAt => '16:00',
            $this->keyPlaceId => SchedulePlace::factory()->create()->id,
            $this->keyUsageId => ScheduleUsage::factory()->create()->id,
            $this->keyTimetableId => ScheduleTimetable::factory()->create()->id,
            $this->keyReservationStatusId => ReservationStatus::factory()->create()->id,
            $this->keyScheduleStatusId => ScheduleStatus::factory()->create()->id,
        ];
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

    public function testYmdListNull()
    {
        $this->postData[$this->keyYmdList] = null;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyYmdList => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testYmdListEmptyArray()
    {
        $this->postData[$this->keyYmdList] = [];
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyYmdList => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testYmdListNotArray()
    {
        $this->postData[$this->keyYmdList] = '2020-12-01,2020-12-02';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyYmdList => [
                    __('validation.array')
                ]
            ]
        );
    }

    public function testYmdListIncludesEmpty()
    {
        array_push($this->postData[$this->keyYmdList], '');
        $lastIndex = count($this->postData[$this->keyYmdList]) - 1;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyYmdList . ".{$lastIndex}" => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testYmdListIncludesNotDate()
    {
        array_push($this->postData[$this->keyYmdList], 'abc');
        $lastIndex = count($this->postData[$this->keyYmdList]) - 1;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyYmdList . ".{$lastIndex}" => [
                    __('validation.date_format')
                ]
            ]
        );
    }

    public function testBeginsAtEmpty()
    {
        $this->postData[$this->keyBeginsAt] = '';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyBeginsAt => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testBeginsAtNotTime()
    {
        $this->postData[$this->keyBeginsAt] = 'abc';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyBeginsAt => [
                    __('validation.date_format')
                ]
            ]
        );
    }

    public function testEndsAtEmpty()
    {
        $this->postData[$this->keyEndsAt] = '';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyEndsAt => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testEndsAtNotTime()
    {
        $this->postData[$this->keyEndsAt] = 'abc';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyEndsAt => [
                    __('validation.date_format')
                ]
            ]
        );
    }

    public function testBeginsAtLessThanEndsAt()
    {
        $this->postData[$this->keyBeginsAt] = '13:00';
        $this->postData[$this->keyEndsAt] = '12:45';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyBeginsAt => [
                    __('validation.time_order')
                ],
                $this->keyEndsAt => [
                    __('validation.time_order')
                ],
            ]
        );
    }

    public function testBeginsAtEqualToEndsAt()
    {
        $this->postData[$this->keyBeginsAt] = '13:00';
        $this->postData[$this->keyEndsAt] = '13:00';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyBeginsAt => [
                    __('validation.time_order')
                ],
                $this->keyEndsAt => [
                    __('validation.time_order')
                ],
            ]
        );
    }

    public function testPlaceIdNull()
    {
        $this->postData[$this->keyPlaceId] = null;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyPlaceId => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testPlaceIdNotExists()
    {
        $this->postData[$this->keyPlaceId] = SchedulePlace::max('id') + 2;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyPlaceId => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testUsageIdNull()
    {
        $this->postData[$this->keyUsageId] = null;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyUsageId => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testUsageIdNotExists()
    {
        $this->postData[$this->keyUsageId] = ScheduleUsage::max('id') + 2;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyUsageId => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testTimetableIdNull()
    {
        $this->postData[$this->keyTimetableId] = null;
        $this->mock(ReservationService::class, function ($mock) {
            $mock->shouldReceive('addScheduleList')
                ->once()
                ->with($this->postData);
        });
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(204);
    }

    public function testTimetableIdNotExists()
    {
        $this->postData[$this->keyTimetableId] = ScheduleTimetable::max('id') + 2;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyTimetableId => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testReservationStatusIdNull()
    {
        $this->postData[$this->keyReservationStatusId] = null;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyReservationStatusId => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testReservationStatusIdNotExists()
    {
        $this->postData[$this->keyReservationStatusId] = ReservationStatus::max('id') + 2;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyReservationStatusId => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testScheduleStatusIdNull()
    {
        $this->postData[$this->keyScheduleStatusId] = null;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyScheduleStatusId => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testScheduleStatusIdNotExists()
    {
        $this->postData[$this->keyScheduleStatusId] = ScheduleStatus::max('id') + 2;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keyScheduleStatusId => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testSuccess()
    {
        $this->mock(ReservationService::class, function ($mock) {
            $mock->shouldReceive('addScheduleList')
                ->once()
                ->with($this->postData);
        });
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(204);
    }
}
