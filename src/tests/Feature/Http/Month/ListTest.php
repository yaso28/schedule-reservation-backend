<?php

namespace Tests\Feature\Http\Month;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Month;
use App\Models\Permission;
use App\Services\ReservationService;

class ListTest extends TestCase
{
    use RefreshDatabase;

    protected $url = '/api/month/list';
    protected $requiredPermissions = [Permission::RESERVATION_READ];

    protected function makeRecords()
    {
        $records = Month::factory()->count(5)->create();
        foreach ($records as $record) {
            $record->reservation_status;
            $record->schedule_status;
        }
        return $records;
    }

    protected function makeExpectedJson($records)
    {
        return [
            'data' => $records->map(fn ($record) => [
                'id' => $record->id,
                'year' => $record->year,
                'month' => $record->month,
                'reservation_status_id' => $record->reservation_status_id,
                'reservation_status' => [
                    'id' => $record->reservation_status->id,
                    'name' => $record->reservation_status->name,
                    'description' => $record->reservation_status->description,
                    'reserved' => $record->reservation_status->reserved,
                ],
                'schedule_status_id' => $record->schedule_status_id,
                'schedule_status' => [
                    'id' => $record->schedule_status->id,
                    'name' => $record->schedule_status->name,
                    'display_type' => $record->schedule_status->display_type,
                    'is_public' => $record->schedule_status->is_public,
                    'bulk_change_mode' => $record->schedule_status->bulk_change_mode,
                ],
                'name' => $record->name,
                'first_day' => $record->first_day,
                'last_day' => $record->last_day,
            ])->toArray(),
        ];
    }

    public function testNoLogin()
    {
        $response = $this->getJson($this->url);
        $response->assertStatus(401);
    }

    public function testNoPermission()
    {
        $response = $this->actingAs($this->createUser())
            ->getJson($this->url);
        $response->assertStatus(403);
    }

    public function testSuccess()
    {
        $records = $this->makeRecords();
        $this->mock(ReservationService::class, function ($mock) use ($records) {
            $mock->shouldReceive('getMonthList')
                ->once()
                ->with(null, null)
                ->andReturn($records);
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson($this->makeExpectedJson($records));
    }

    public function testSuccessWithQuery()
    {
        $yearFrom = 2020;
        $monthFrom = 11;

        $records = $this->makeRecords();
        $this->mock(ReservationService::class, function ($mock) use ($records, $yearFrom, $monthFrom) {
            $mock->shouldReceive('getMonthList')
                ->once()
                ->with(strval($yearFrom), strval($monthFrom))
                ->andReturn($records);
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->getJson("{$this->url}?year_from={$yearFrom}&month_from={$monthFrom}");

        $response->assertStatus(200)
            ->assertJson($this->makeExpectedJson($records));
    }
}
