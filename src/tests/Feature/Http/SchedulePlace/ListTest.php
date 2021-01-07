<?php

namespace Tests\Feature\Http\SchedulePlace;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\SchedulePlace;
use App\Services\ScheduleMasterService;

class ListTest extends TestCase
{
    protected $url = '/api/schedule-place/list';

    public function testSuccess()
    {
        $records = SchedulePlace::factory()->count(3)->make();
        for ($i = 0; $i < $records->count(); $i++) {
            $records[$i]->id = $i + 1;
        }

        $this->mock(ScheduleMasterService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getSchedulePlaceList')
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
                    'price_per_hour' => $record->price_per_hour,
                ])->toArray(),
            ]);
    }
}
