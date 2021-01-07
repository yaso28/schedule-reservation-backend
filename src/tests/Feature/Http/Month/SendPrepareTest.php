<?php

namespace Tests\Feature\Http\Month;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Month;
use App\Models\Permission;
use App\Services\MonthScheduleService;
use Tests\Utilities\DataHelper;
use Illuminate\Support\Arr;

class SendPrepareTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $url;
    protected $id;

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/month/send/prepare/{$id}";
    }

    public function testNoLogin()
    {
        $this->setIdAndUrl(5);
        $response = $this->getJson($this->url);
        $response->assertStatus(401);
    }

    public function testNoPermission()
    {
        $this->setIdAndUrl(5);
        $response = $this->actingAs($this->createUser())
            ->getJson($this->url);
        $response->assertStatus(403);
    }

    public function testPermissionReadOnly()
    {
        $this->setIdAndUrl(5);
        $response = $this->actingAs($this->createUser([Permission::RESERVATION_READ]))
            ->getJson($this->url);
        $response->assertStatus(403);
    }

    public function testSuccess()
    {
        $month = Month::factory()->create();
        $month->reservation_status;
        $month->schedule_status;
        $this->setIdAndUrl($month->id);
        $expectedSendInfo = [
            'mail_to' => $this->faker->safeEmail,
            'subject' => DataHelper::randomText(),
            'message' => $this->faker->realText(500),
        ];
        $this->mock(MonthScheduleService::class, function ($mock) use ($month, $expectedSendInfo) {
            $mock->shouldReceive('prepareSendInfo')
                ->once()
                ->with($this->id)
                ->andReturn(Arr::add($expectedSendInfo, 'month', $month));
        });

        $response = $this->actingAs($this->createUser([Permission::RESERVATION_READ, Permission::RESERVATION_WRITE]))
            ->getJson($this->url);
        $response->assertStatus(200)
            ->assertJson(['data' => Arr::add($expectedSendInfo, 'month', [
                'id' => $month->id,
                'year' => $month->year,
                'month' => $month->month,
                'reservation_status_id' => $month->reservation_status_id,
                'reservation_status' => [
                    'id' => $month->reservation_status->id,
                    'name' => $month->reservation_status->name,
                    'description' => $month->reservation_status->description,
                    'reserved' => $month->reservation_status->reserved,
                ],
                'schedule_status_id' => $month->schedule_status_id,
                'schedule_status' => [
                    'id' => $month->schedule_status->id,
                    'name' => $month->schedule_status->name,
                    'display_type' => $month->schedule_status->display_type,
                    'is_public' => $month->schedule_status->is_public,
                    'bulk_change_mode' => $month->schedule_status->bulk_change_mode,
                ],
                'name' => $month->name,
                'first_day' => $month->first_day,
                'last_day' => $month->last_day,
            ])]);
    }
}
