<?php

namespace Tests\Feature\Http\ScheduleStatus;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ScheduleStatus;
use App\Services\ScheduleMasterService;

class ListTest extends TestCase
{
    protected $url = '/api/schedule-status/list';

    public function testSuccess()
    {
        $records = ScheduleStatus::factory()->count(4)->make();
        for ($i = 0; $i < $records->count(); $i++) {
            $records[$i]->id = $i + 1;
        }

        $this->mock(ScheduleMasterService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getScheduleStatusList')
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
                    'display_type' => $record->display_type,
                    'is_public' => $record->is_public,
                    'bulk_change_mode' => $record->bulk_change_mode,
                ])->toArray(),
            ]);
    }
}
