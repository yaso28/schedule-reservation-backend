<?php

namespace Tests\Feature\Http\Schedule;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Schedule;
use App\Models\Permission;
use App\Services\ReservationService;
use App\Services\FormatService;

class GetTest extends TestCase
{
    use RefreshDatabase;

    protected $url;
    protected $id;

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/schedule/get/{$id}";
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
        $record = Schedule::factory()->create();
        $record->schedule_place;
        $record->schedule_usage;
        $record->schedule_timetable;
        $record->schedule_status;
        $record->reservation_status;
        $record->schedule_usage->reservation_organization;
        $this->setIdAndUrl($record->id);

        $this->mock(ReservationService::class, function ($mock) use ($record) {
            $mock->shouldReceive('getSchedule')
                ->once()
                ->with($this->id)
                ->andReturn($record);
        });

        $response = $this->actingAs($this->createUser([Permission::RESERVATION_READ,]))
            ->getJson($this->url);

        $formatService = resolve(FormatService::class);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $record->id,
                    'ymd' => $formatService->date($record->ymd),
                    'begins_at' => $formatService->time($record->begins_at),
                    'ends_at' => $formatService->time($record->ends_at),
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
                        'is_public' => $formatService->bool($record->schedule_usage->is_public),
                        'reservation_organization_id' => $record->schedule_usage->reservation_organization_id,
                        'reservation_organization' => [
                            'id' => $record->schedule_usage->reservation_organization->id,
                            'name' => $record->schedule_usage->reservation_organization->name,
                            'abbreviation' => $record->schedule_usage->reservation_organization->abbreviation,
                            'registration_number' => $record->schedule_usage->reservation_organization->registration_number,
                        ],
                    ],
                    'schedule_timetable_id' => $record->schedule_timetable_id,
                    'schedule_timetable' => [
                        'id' => $record->schedule_timetable->id,
                        'name' => $record->schedule_timetable->name,
                        'details' => $record->schedule_timetable->details,
                    ],
                    'reservation_status_id' => $record->reservation_status_id,
                    'reservation_status' => [
                        'id' => $record->reservation_status->id,
                        'name' => $record->reservation_status->name,
                        'description' => $record->reservation_status->description,
                        'reserved' => $formatService->bool($record->reservation_status->reserved),
                    ],
                    'schedule_status_id' => $record->schedule_status_id,
                    'schedule_status' => [
                        'id' => $record->schedule_status->id,
                        'name' => $record->schedule_status->name,
                        'display_type' => $record->schedule_status->display_type,
                        'is_public' => $formatService->bool($record->schedule_status->is_public),
                        'bulk_change_mode' => $record->schedule_status->bulk_change_mode,
                    ],
                ],
            ]);
    }
}
