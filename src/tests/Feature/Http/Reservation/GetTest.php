<?php

namespace Tests\Feature\Http\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Reservation;
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
        $this->url = "/api/reservation/get/{$id}";
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
        $record = Reservation::factory()->create();
        $record->schedule;
        $record->schedule->schedule_place;
        $record->schedule->schedule_usage;
        $record->schedule->schedule_usage->reservation_organization;
        $record->reservation_status;
        $this->setIdAndUrl($record->id);

        $this->mock(ReservationService::class, function ($mock) use ($record) {
            $mock->shouldReceive('getReservation')
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
                    'schedule_id' => $record->schedule_id,
                    'schedule' => [
                        'id' => $record->schedule->id,
                        'ymd' => $formatService->date($record->schedule->ymd),
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
                        'reserved' => $formatService->bool($record->reservation_status->reserved),
                    ],
                ],
            ]);
    }
}
