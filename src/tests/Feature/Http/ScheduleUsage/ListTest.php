<?php

namespace Tests\Feature\Http\ScheduleUsage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ScheduleUsage;
use App\Models\ReservationOrganization;
use App\Services\ScheduleMasterService;

class ListTest extends TestCase
{
    use RefreshDatabase;

    protected $url = '/api/schedule-usage/list';

    public function testSuccess()
    {
        $records = ScheduleUsage::factory()->count(3)->create();
        foreach ($records as $record) {
            $record->reservation_organization;
        }

        $this->mock(ScheduleMasterService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getScheduleUsageList')
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
                    'is_public' => $record->is_public,
                    'reservation_organization_id' => $record->reservation_organization_id,
                    'reservation_organization' => [
                        'id' => $record->reservation_organization->id,
                        'name' => $record->reservation_organization->name,
                        'abbreviation' => $record->reservation_organization->abbreviation,
                        'registration_number' => $record->reservation_organization->registration_number,
                    ],
                ])->toArray(),
            ]);
    }
}
