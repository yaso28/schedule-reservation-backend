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
use App\Services\ReservationService;

class ListPublicTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $url = '/api/schedule/list/public';

    protected function makeRecords()
    {
        $placeList = SchedulePlace::factory()->count(5)->create();
        $usageList = ScheduleUsage::factory()->count(5)->create();
        $timetableList = ScheduleTimetable::factory()->count(10)->create();
        $reservationStatusList = ReservationStatus::factory()->count(10)->create();
        $scheduleStatusList = ScheduleStatus::factory()->count(5)->create();

        $records = Schedule::factory()->count(50)->create([
            'schedule_place_id' => $this->faker->randomElement($placeList)->id,
            'schedule_usage_id' => $this->faker->randomElement($usageList)->id,
            'schedule_timetable_id' => $this->faker->randomElement($timetableList)->id,
            'reservation_status_id' => $this->faker->randomElement($reservationStatusList)->id,
            'schedule_status_id' => $this->faker->randomElement($scheduleStatusList)->id,
        ]);
        foreach ($records as $record) {
            $record->schedule_place;
            $record->schedule_usage;
            $record->schedule_timetable;
            $record->schedule_status;
        }
        return $records;
    }

    protected function makeExpectedJson($records)
    {
        return [
            'data' => $records->map(fn ($record) => [
                'id' => $record->id,
                'ymd' => $record->ymd,
                'ends_at' => $record->ends_at,
                'schedule_place_id' => $record->schedule_place_id,
                'schedule_place' => [
                    'id' => $record->schedule_place->id,
                    'name' => $record->schedule_place->name,
                    'abbreviation' => $record->schedule_place->abbreviation,
                    'price_per_hour' => $record->schedule_place->price_per_hour,
                ],
                'schedule_usage_id' => $record->schedule_usage_id,
                'schedule_usage' => [
                    'id' => $record->schedule_usage->id,
                    'name' => $record->schedule_usage->name,
                    'is_public' => $record->schedule_usage->is_public,
                    'reservation_organization_id' => $record->schedule_usage->reservation_organization_id,
                ],
                'schedule_timetable_id' => $record->schedule_timetable_id,
                'schedule_timetable' => [
                    'id' => $record->schedule_timetable->id,
                    'name' => $record->schedule_timetable->name,
                    'details' => $record->schedule_timetable->details,
                ],
                'reservation_status_id' => $record->reservation_status_id,
                'schedule_status_id' => $record->schedule_status_id,
                'schedule_status' => [
                    'id' => $record->schedule_status->id,
                    'name' => $record->schedule_status->name,
                    'display_type' => $record->schedule_status->display_type,
                    'is_public' => $record->schedule_status->is_public,
                    'bulk_change_mode' => $record->schedule_status->bulk_change_mode,
                ],
            ])->toArray(),
        ];
    }


    public function testSuccess()
    {
        $records = $this->makeRecords();
        $this->mock(ReservationService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getScheduleList')
                ->once()
                ->with(true)
                ->andReturn($records);
        });

        $response = $this->getJson($this->url);
        $response->assertStatus(200)
            ->assertJson($this->makeExpectedJson($records));
    }
}
