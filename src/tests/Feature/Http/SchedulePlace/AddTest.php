<?php

namespace Tests\Feature\Http\SchedulePlace;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Permission;
use App\Models\SchedulePlace;
use App\Services\ScheduleMasterService;

class AddTest extends TestCase
{
    use RefreshDatabase;

    protected $url = '/api/schedule-place/add';
    protected $requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];
    protected $attributes = [
        'name' => '体育館＠公民館別棟',
        'abbreviation' => '体育館',
        'price_per_hour' => 0,
    ];
    protected $id = 5;

    public function testNoLogin()
    {
        $response = $this->postJson($this->url, $this->attributes);

        $response->assertStatus(401);
    }

    public function testNoPermission()
    {
        $response = $this->actingAs($this->createUser())
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(403);
    }

    public function testPermissionReadOnly()
    {
        $response = $this->actingAs($this->createUser([Permission::RESERVATION_READ]))
            ->postJson($this->url, $this->attributes);
        $response->assertStatus(403);
    }

    public function testNameEmpty()
    {
        $this->attributes['name'] = '';

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'name' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testNameNotUnique()
    {
        SchedulePlace::factory()->create([
            'name' => $this->attributes['name']
        ]);

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'name' => [
                    __('validation.unique')
                ]
            ]
        );
    }

    public function testAbbreviationEmpty()
    {
        $this->attributes['abbreviation'] = '';

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'abbreviation' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testAbbreviationNotUnique()
    {
        SchedulePlace::factory()->create([
            'abbreviation' => $this->attributes['abbreviation']
        ]);

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'abbreviation' => [
                    __('validation.unique')
                ]
            ]
        );
    }

    public function testPricePerHourEmpty()
    {
        $this->attributes['price_per_hour'] = null;

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'price_per_hour' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testPricePerHourNotInteger()
    {
        $this->attributes['price_per_hour'] = 1.25;

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'price_per_hour' => [
                    __('validation.integer')
                ]
            ]
        );
    }

    public function testPricePerHourMinusValue()
    {
        $this->attributes['price_per_hour'] = -1;

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'price_per_hour' => [
                    __('validation.min.numeric', [
                        'min' => 0,
                    ])
                ]
            ]
        );
    }

    public function testSuccess()
    {
        $this->mock(ScheduleMasterService::class, function ($mock) {
            $mock->shouldReceive('addSchedulePlace')
                ->once()
                ->with($this->attributes)
                ->andReturn($this->id);
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->id,
                ],
            ]);
    }
}
