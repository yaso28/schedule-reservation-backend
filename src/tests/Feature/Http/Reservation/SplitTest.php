<?php

namespace Tests\Feature\Http\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Permission;
use App\Services\ReservationService;
use Illuminate\Validation\ValidationException;

class SplitTest extends TestCase
{
    use RefreshDatabase;

    protected $url;
    protected $requiredPermissions;
    protected $keySplitsAt = 'splits_at';
    protected $id;
    protected $splitsAt;
    protected $formInput;
    protected $scheduleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];
        $this->setIdAndUrl(24);
        $this->setFormInput('13:00');
        $this->scheduleId = 10;
    }

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/reservation/split/{$id}";
    }

    protected function setFormInput($splitsAt)
    {
        $this->splitsAt = $splitsAt;
        $this->formInput = [$this->keySplitsAt => $splitsAt];
    }


    public function testNoLogin()
    {
        $response = $this->postJson($this->url, $this->formInput);
        $response->assertStatus(401);
    }

    public function testNoPermission()
    {
        $response = $this->actingAs($this->createUser())
            ->postJson($this->url, $this->formInput);
        $response->assertStatus(403);
    }

    public function testPermissionReadOnly()
    {
        $response = $this->actingAs($this->createUser([Permission::RESERVATION_READ]))
            ->postJson($this->url, $this->formInput);
        $response->assertStatus(403);
    }

    public function testSplitsAtEmpty()
    {
        $this->setFormInput('');
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->formInput);
        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keySplitsAt => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testSplitsAtNotTime()
    {
        $this->setFormInput('aovh');
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->formInput);
        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keySplitsAt => [
                    __('validation.date_format')
                ]
            ]
        );
    }

    public function testSplitsAtInvalidTimeOrder()
    {
        $this->mock(ReservationService::class, function ($mock) {
            $mock->shouldReceive('splitReservation')
                ->once()
                ->with($this->id, $this->splitsAt)
                ->andThrow(ValidationException::withMessages([
                    $this->keySplitsAt => __('validation.time_order'),
                ]));
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->formInput);
        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $this->keySplitsAt => [
                    __('validation.time_order')
                ]
            ]
        );
    }

    public function testSuccess()
    {
        $this->mock(ReservationService::class, function ($mock) {
            $mock->shouldReceive('splitReservation')
                ->once()
                ->with($this->id, $this->splitsAt)
                ->andReturn($this->scheduleId);
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->formInput);
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'schedule_id' => $this->scheduleId,
                ],
            ]);
    }
}
