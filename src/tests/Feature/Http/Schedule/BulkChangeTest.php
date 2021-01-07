<?php

namespace Tests\Feature\Http\Schedule;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Schedule;
use App\Models\SchedulePlace;
use App\Models\ScheduleTimetable;
use App\Models\ScheduleUsage;
use App\Models\ScheduleStatus;
use App\Models\ReservationStatus;
use App\Models\Permission;
use App\Services\ReservationService;

class BulkChangeTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $url = '/api/schedule/bulk-change';
    protected $requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];

    protected $placeList;
    protected $usageList;
    protected $timetableList;
    protected $reservationStatusList;
    protected $scheduleList;
    protected $scheduleStatusList;

    protected $idList;
    protected $scheduleStatusId;
    protected $postData;

    protected function setUp(): void
    {
        parent::setUp();

        // make records
        $this->placeList = SchedulePlace::factory()->count(2)->create();
        $this->usageList = ScheduleUsage::factory()->count(2)->create();
        $this->timetableList = ScheduleTimetable::factory()->count(2)->create();
        $this->reservationStatusList = ReservationStatus::factory()->count(2)->create();
        $this->scheduleStatusList = ScheduleStatus::factory()->count(5)->create();
        $this->scheduleList = Schedule::factory()->count(10)->create([
            'schedule_place_id' => $this->faker->randomElement($this->placeList)->id,
            'schedule_usage_id' => $this->faker->randomElement($this->usageList)->id,
            'schedule_timetable_id' => $this->faker->randomElement($this->timetableList)->id,
            'reservation_status_id' => $this->faker->randomElement($this->reservationStatusList)->id,
            'schedule_status_id' => $this->faker->randomElement($this->scheduleStatusList)->id,
        ]);

        // make post data
        $this->idList = $this->scheduleList->map(fn ($record) => $record->id)->shuffle()->take(5)->toArray();
        $this->scheduleStatusId = $this->faker->randomElement($this->scheduleStatusList)->id;
        $this->postData = [
            'id_list' => $this->idList,
            'schedule_status_id' => $this->scheduleStatusId,
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

    public function testIdListNull()
    {
        $this->postData['id_list'] = null;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'id_list' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testIdListEmptyArray()
    {
        $this->postData['id_list'] = [];
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'id_list' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testIdListNotArray()
    {
        $this->postData['id_list'] = '1,2,3';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'id_list' => [
                    __('validation.array')
                ]
            ]
        );
    }

    public function testIdListIncludesNotExists()
    {
        array_push($this->postData['id_list'], Schedule::max('id') + 100);
        $lastIndex = count($this->postData['id_list']) - 1;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                "id_list.{$lastIndex}" => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testScheduleStatusIdNull()
    {
        $this->postData['schedule_status_id'] = null;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'schedule_status_id' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testScheduleStatusIdNotExists()
    {
        $this->postData['schedule_status_id'] = ScheduleStatus::max('id') + 100;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'schedule_status_id' => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testSuccess()
    {
        $this->mock(ReservationService::class, function ($mock) {
            $mock->shouldReceive('bulkChangeSchedule')
                ->once()
                ->with($this->idList, [
                    'schedule_status_id' => $this->scheduleStatusId,
                ]);
        });
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(204);
    }
}
