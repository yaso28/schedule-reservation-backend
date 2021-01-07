<?php

namespace Tests\Feature\Http\ScheduleTimetable;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ScheduleTimetable;
use App\Services\ScheduleMasterService;

class GetTest extends TestCase
{
    protected $url;
    protected $id;

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/schedule-timetable/get/{$id}";
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setIdAndUrl(5);
    }

    public function testSuccess()
    {
        $record = ScheduleTimetable::factory()->make();
        $record->id = $this->id;

        $this->mock(ScheduleMasterService::class, function ($mock) use ($record) {
            $mock->shouldReceive('getScheduleTimetable')
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
                    'details' => $record->details,
                ],
            ]);
    }
}
