<?php

namespace Tests\Feature\Http\ReservationOrganization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ReservationOrganization;
use App\Services\ScheduleMasterService;

class GetTest extends TestCase
{
    protected $url;
    protected $id;

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/reservation-organization/get/{$id}";
    }

    public function testSuccess()
    {
        $record = ReservationOrganization::factory()->make();
        $record->id = 5;
        $this->setIdAndUrl($record->id);

        $this->mock(ScheduleMasterService::class, function ($mock) use ($record) {
            $mock->shouldReceive('getReservationOrganization')
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
                    'abbreviation' => $record->abbreviation,
                    'registration_number' => $record->registration_number,
                ],
            ]);
    }
}
