<?php

namespace Tests\Feature\Http\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Permission;
use App\Services\ReservationService;

class ListForScheduleTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $url;
    protected $requiredPermissions = [Permission::RESERVATION_READ];
    protected $scheduleId;

    protected function setIdAndUrl($scheduleId)
    {
        $this->scheduleId = $scheduleId;
        $this->url = "/api/reservation/list-for-schedule/{$scheduleId}";
    }

    protected function makeRecords()
    {
        $schedule = Schedule::factory()->create();
        $records = Reservation::factory()->count(2)->create([
            'schedule_id' => $schedule->id,
        ]);
        foreach ($records as $record) {
            $record->reservation_status;
        }
        return $records;
    }

    protected function makeExpectedJson($records)
    {
        return [
            'data' => $records->map(fn ($record) => [
                'id' => $record->id,
                'schedule_id' => $record->schedule_id,
                'begins_at' => $record->begins_at,
                'ends_at' => $record->ends_at,
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
        $this->setIdAndUrl(5);
        $response = $this->getJson($this->url);
        $response->assertStatus(401);
    }

    public function testNoPermission()
    {
        $this->setIdAndUrl(5);
        $response = $this->actingAs($this->createUser())
            ->getJson($this->url);
        $response->assertStatus(403);
    }

    public function testSuccess()
    {
        $records = $this->makeRecords();
        $this->setIdAndUrl($records[0]->schedule_id);
        $this->mock(ReservationService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getReservationListForSchedule')
                ->once()
                ->with($this->scheduleId)
                ->andReturn($records);
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson($this->makeExpectedJson($records));
    }
}
