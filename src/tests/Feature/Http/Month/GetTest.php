<?php

namespace Tests\Feature\Http\Month;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Month;
use App\Models\Permission;
use App\Services\ReservationService;

class GetTest extends TestCase
{
    use RefreshDatabase;

    protected $url;
    protected $id;

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/month/get/{$id}";
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
        $record = Month::factory()->create();
        $record->reservation_status;
        $record->schedule_status;
        $this->setIdAndUrl($record->id);

        $this->mock(ReservationService::class, function ($mock) use ($record) {
            $mock->shouldReceive('getMonth')
                ->once()
                ->with($this->id)
                ->andReturn($record);
        });

        $response = $this->actingAs($this->createUser([Permission::RESERVATION_READ]))
            ->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $record->id,
                    'year' => $record->year,
                    'month' => $record->month,
                    'reservation_status_id' => $record->reservation_status_id,
                    'reservation_status' => [
                        'id' => $record->reservation_status->id,
                        'name' => $record->reservation_status->name,
                        'description' => $record->reservation_status->description,
                        'reserved' => $record->reservation_status->reserved,
                    ],
                    'schedule_status_id' => $record->schedule_status_id,
                    'schedule_status' => [
                        'id' => $record->schedule_status->id,
                        'name' => $record->schedule_status->name,
                        'display_type' => $record->schedule_status->display_type,
                        'is_public' => $record->schedule_status->is_public,
                        'bulk_change_mode' => $record->schedule_status->bulk_change_mode,
                    ],
                    'name' => $record->name,
                    'first_day' => $record->first_day,
                    'last_day' => $record->last_day,
                ],
            ]);
    }
}
