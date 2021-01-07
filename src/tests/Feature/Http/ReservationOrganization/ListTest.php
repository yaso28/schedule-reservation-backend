<?php

namespace Tests\Feature\Http\ReservationOrganization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ReservationOrganization;
use App\Services\ScheduleMasterService;

class ListTest extends TestCase
{
    protected $url = '/api/reservation-organization/list';

    public function testSuccess()
    {
        $records = ReservationOrganization::factory()->count(2)->make();
        for ($i = 0; $i < $records->count(); $i++) {
            $records[$i]->id = $i + 1;
        }

        $this->mock(ScheduleMasterService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getReservationOrganizationList')
                ->once()
                ->withNoArgs()
                ->andReturn($records);
        });

        $response = $this->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson([
                'data' => $records->map(fn ($record) => [
                    'id' => $record->id,
                    'name' => $record->name,
                    'abbreviation' => $record->abbreviation,
                    'registration_number' => $record->registration_number,
                ])->toArray(),
            ]);
    }
}
