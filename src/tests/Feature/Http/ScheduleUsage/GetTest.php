<?php

namespace Tests\Feature\Http\ScheduleUsage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ScheduleUsage;
use App\Models\ReservationOrganization;
use App\Services\ScheduleMasterService;

class GetTest extends TestCase
{
    use RefreshDatabase;

    protected $url;
    protected $id;

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/schedule-usage/get/{$id}";
    }

    public function testSuccess()
    {
        $record = ScheduleUsage::factory()->create();
        $record->reservation_organization;
        $this->setIdAndUrl($record->id);

        $this->mock(ScheduleMasterService::class, function ($mock) use ($record) {
            $mock->shouldReceive('getScheduleUsage')
                ->once()
                ->with($this->id)
                ->andReturn($record);
        });

        $response = $this->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $record->id,
                    'name' => $record->name,
                    'is_public' => $record->is_public,
                    'reservation_organization_id' => $record->reservation_organization_id,
                    'reservation_organization' => [
                        'id' => $record->reservation_organization->id,
                        'name' => $record->reservation_organization->name,
                        'abbreviation' => $record->reservation_organization->abbreviation,
                        'registration_number' => $record->reservation_organization->registration_number,
                    ],
                ],
            ]);
    }
}
