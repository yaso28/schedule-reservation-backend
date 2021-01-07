<?php

namespace Tests\Feature\Http\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Reservation;
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

    protected $url = '/api/reservation/bulk-change';
    protected $requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];

    protected $placeList;
    protected $usageList;
    protected $timetableList;
    protected $reservationStatusList;
    protected $scheduleList;
    protected $reservationList;
    protected $scheduleStatusList;

    protected $idList;
    protected $reservationStatusId;
    protected $postData;

    protected function setUp(): void
    {
        parent::setUp();

        // make records
        $this->placeList = SchedulePlace::factory()->count(2)->create();
        $this->usageList = ScheduleUsage::factory()->count(2)->create();
        $this->timetableList = ScheduleTimetable::factory()->count(2)->create();
        $this->reservationStatusList = ReservationStatus::factory()->count(10)->create();
        $this->scheduleStatusList = ScheduleStatus::factory()->count(2)->create();
        $this->scheduleList = Schedule::factory()->count(10)->create([
            'schedule_place_id' => $this->faker->randomElement($this->placeList)->id,
            'schedule_usage_id' => $this->faker->randomElement($this->usageList)->id,
            'schedule_timetable_id' => $this->faker->randomElement($this->timetableList)->id,
            'reservation_status_id' => $this->faker->randomElement($this->reservationStatusList)->id,
            'schedule_status_id' => $this->faker->randomElement($this->scheduleStatusList)->id,
        ]);
        $this->reservationList = Reservation::factory()->count(10)->create([
            'schedule_id' => $this->faker->randomElement($this->scheduleList)->id,
            'reservation_status_id' => $this->faker->randomElement($this->reservationStatusList)->id,
        ]);

        // make post data
        $this->idList = $this->reservationList->map(fn ($record) => $record->id)->shuffle()->take(5)->toArray();
        $this->reservationStatusId = $this->faker->randomElement($this->reservationStatusList)->id;
        $this->postData = [
            'id_list' => $this->idList,
            'reservation_status_id' => $this->reservationStatusId,
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
        array_push($this->postData['id_list'], Reservation::max('id') + 100);
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

    public function testReservationStatusIdNull()
    {
        $this->postData['reservation_status_id'] = null;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'reservation_status_id' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testReservationStatusIdNotExists()
    {
        $this->postData['reservation_status_id'] = ReservationStatus::max('id') + 100;
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'reservation_status_id' => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testSuccess()
    {
        $this->mock(ReservationService::class, function ($mock) {
            $mock->shouldReceive('bulkChangeReservation')
                ->once()
                ->with($this->idList, [
                    'reservation_status_id' => $this->reservationStatusId,
                ]);
        });
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);

        $response->assertStatus(204);
    }
}
