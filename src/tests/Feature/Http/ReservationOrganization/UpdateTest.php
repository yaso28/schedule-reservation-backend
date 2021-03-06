<?php

namespace Tests\Feature\Http\ReservationOrganization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Permission;
use App\Models\ReservationOrganization;
use App\Services\ScheduleMasterService;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $url;
    protected $requiredPermissions;
    protected $id;
    protected $attributes;

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/reservation-organization/update/{$id}";
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setIdAndUrl(5);
        $this->requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];
        $this->attributes = [
            'name' => '団体名称',
            'abbreviation' => '略称',
            'registration_number' => '1234-567890',
        ];
    }

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
        $existingRecord = ReservationOrganization::factory()->create([
            'name' => $this->attributes['name']
        ]);
        $this->setIdAndUrl($existingRecord->id + 1);

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
        $existingRecord = ReservationOrganization::factory()->create([
            'abbreviation' => $this->attributes['abbreviation']
        ]);
        $this->setIdAndUrl($existingRecord->id + 1);

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

    public function testRegistrationNumberEmpty()
    {
        $this->attributes['registration_number'] = '';

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'registration_number' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testRegistrationNumberNotUnique()
    {
        $existingRecord = ReservationOrganization::factory()->create([
            'registration_number' => $this->attributes['registration_number']
        ]);
        $this->setIdAndUrl($existingRecord->id + 1);

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'registration_number' => [
                    __('validation.unique')
                ]
            ]
        );
    }

    public function testSuccess()
    {
        $this->mock(ScheduleMasterService::class, function ($mock) {
            $mock->shouldReceive('updateReservationOrganization')
                ->once()
                ->with($this->id, $this->attributes)
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

    /**
     * 値を変更せずに更新してもUniqueバリデーションが通ることを検証
     * (Uniqueバリデーションの比較対象から自分自身の変更前の値が除外されていることを確認)
     */
    public function testSuccessNoUpdate()
    {
        $existingRecord = ReservationOrganization::factory()->create($this->attributes);
        $this->setIdAndUrl($existingRecord->id);
        $this->mock(ScheduleMasterService::class, function ($mock) {
            $mock->shouldReceive('updateReservationOrganization')
                ->once()
                ->with($this->id, $this->attributes)
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
