<?php

namespace Tests\Feature\Http\ReservationStatus;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ReservationStatus;
use App\Services\ScheduleMasterService;

class ListTest extends TestCase
{
    protected $url = '/api/reservation-status/list';

    public function testSuccess()
    {
        $records = ReservationStatus::factory()->count(10)->make();
        for ($i = 0; $i < $records->count(); $i++) {
            $records[$i]->id = $i + 1;
        }

        $this->mock(ScheduleMasterService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getReservationStatusList')
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
                    'description' => $record->description,
                    'reserved' => $record->reserved,
                ])->toArray(),
            ]);
    }
}
