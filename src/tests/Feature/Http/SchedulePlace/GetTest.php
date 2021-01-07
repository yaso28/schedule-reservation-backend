<?php

namespace Tests\Feature\Http\SchedulePlace;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\SchedulePlace;
use App\Services\ScheduleMasterService;

class GetTest extends TestCase
{
    protected $url;
    protected $id;

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/schedule-place/get/{$id}";
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setIdAndUrl(5);
    }

    public function testSuccess()
    {
        $record = SchedulePlace::factory()->make();
        $record->id = $this->id;

        $this->mock(ScheduleMasterService::class, function ($mock) use ($record) {
            $mock->shouldReceive('getSchedulePlace')
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
                    'price_per_hour' => $record->price_per_hour,
                ],
            ]);
    }
}
