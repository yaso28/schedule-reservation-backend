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
use App\Services\FormatService;

class ListTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $url = '/api/reservation/list';
    protected $requiredPermissions = [Permission::RESERVATION_READ];

    protected function makeRecords()
    {
        $placeList = SchedulePlace::factory()->count(5)->create();
        $usageList = ScheduleUsage::factory()->count(5)->create();
        $timetableList = ScheduleTimetable::factory()->count(10)->create();
        $reservationStatusList = ReservationStatus::factory()->count(10)->create();
        $scheduleStatusList = ScheduleStatus::factory()->count(5)->create();

        $records = Reservation::factory()->count(50)->create([
            'schedule_id' => Schedule::factory()->create([
                'schedule_place_id' => $this->faker->randomElement($placeList)->id,
                'schedule_usage_id' => $this->faker->randomElement($usageList)->id,
                'schedule_timetable_id' => $this->faker->randomElement($timetableList)->id,
                'reservation_status_id' => $this->faker->randomElement($reservationStatusList)->id,
                'schedule_status_id' => $this->faker->randomElement($scheduleStatusList)->id,
            ])->id,
            'reservation_status_id' => $this->faker->randomElement($reservationStatusList)->id,
        ]);
        foreach ($records as $record) {
            $record->schedule;
            $record->schedule->schedule_place;
            $record->schedule->schedule_usage;
            $record->schedule->schedule_usage->reservation_organization;
            $record->reservation_status;
        }
        return $records;
    }

    protected function makeExpectedJson($records)
    {
        $formatService = resolve(FormatService::class);

        return [
            'data' => $records->map(fn ($record) => [
                'id' => $record->id,
                'schedule_id' => $record->schedule_id,
                'schedule' => [
                    'id' => $record->schedule->id,
                    'ymd' => $record->schedule->ymd,
                    'begins_at' => $formatService->time($record->schedule->begins_at),
                    'ends_at' => $formatService->time($record->schedule->ends_at),
                    'schedule_place_id' => $record->schedule->schedule_place_id,
                    'schedule_place' => [
                        'id' => $record->schedule->schedule_place->id,
                        'name' => $record->schedule->schedule_place->name,
                        'abbreviation' => $record->schedule->schedule_place->abbreviation,
                        'price_per_hour' => $record->schedule->schedule_place->price_per_hour,
                    ],
                    'schedule_usage_id' => $record->schedule->schedule_usage_id,
                    'schedule_usage' => [
                        'id' => $record->schedule->schedule_usage->id,
                        'name' => $record->schedule->schedule_usage->name,
                        'is_public' => $formatService->bool($record->schedule->schedule_usage->is_public),
                        'reservation_organization_id' => $record->schedule->schedule_usage->reservation_organization_id,
                        'reservation_organization' => [
                            'id' => $record->schedule->schedule_usage->reservation_organization->id,
                            'name' => $record->schedule->schedule_usage->reservation_organization->name,
                            'abbreviation' => $record->schedule->schedule_usage->reservation_organization->abbreviation,
                            'registration_number' => $record->schedule->schedule_usage->reservation_organization->registration_number,
                        ],
                    ],
                    'schedule_timetable_id' => $record->schedule->schedule_timetable_id,
                    'reservation_status_id' => $record->schedule->reservation_status_id,
                    'schedule_status_id' => $record->schedule->schedule_status_id,
                ],
                'begins_at' => $formatService->time($record->begins_at),
                'ends_at' => $formatService->time($record->ends_at),
                'reservation_status_id' => $record->reservation_status_id,
                'reservation_status' => [
                    'id' => $record->reservation_status->id,
                    'name' => $record->reservation_status->name,
                    'description' => $record->reservation_status->description,
                    'reserved' => $record->reservation_status->reserved,
                ],
            ])->toArray(),
        ];
    }


    public function testNoLogin()
    {
        $response = $this->getJson($this->url);
        $response->assertStatus(401);
    }

    public function testNoPermission()
    {
        $response = $this->actingAs($this->createUser())
            ->getJson($this->url);
        $response->assertStatus(403);
    }

    public function testSuccess()
    {
        $records = $this->makeRecords();
        $this->mock(ReservationService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getReservationList')
                ->once()
                ->with(null, null)
                ->andReturn($records);
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson($this->makeExpectedJson($records));
    }

    public function testSuccessWithQuery()
    {
        $from = '2020-11-01';
        $to = '2020-11-30';

        $records = $this->makeRecords();
        $this->mock(ReservationService::class, function ($mock) use ($records, $from, $to) {
            $mock->shouldReceive('getReservationList')
                ->once()
                ->with($from, $to)
                ->andReturn($records);
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->getJson("{$this->url}?from={$from}&to={$to}");

        $response->assertStatus(200)
            ->assertJson($this->makeExpectedJson($records));
    }
}
