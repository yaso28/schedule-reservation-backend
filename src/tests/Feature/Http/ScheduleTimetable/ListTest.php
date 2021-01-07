<?php

namespace Tests\Feature\Http\ScheduleTimetable;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ScheduleTimetable;
use App\Services\ScheduleMasterService;

class ListTest extends TestCase
{
    protected $url = '/api/schedule-timetable/list';

    public function testSuccess()
    {
        $records = ScheduleTimetable::factory()->count(10)->make();
        for ($i = 0; $i < $records->count(); $i++) {
            $records[$i]->id = $i + 1;
        }

        $this->mock(ScheduleMasterService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getScheduleTimetableList')
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
                    'details' => $record->details,
                ])->toArray(),
            ]);
    }
}
