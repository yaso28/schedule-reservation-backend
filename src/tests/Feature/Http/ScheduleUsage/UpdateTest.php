<?php

namespace Tests\Feature\Http\ScheduleUsage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Permission;
use App\Models\ScheduleUsage;
use App\Services\ScheduleMasterService;
use App\Models\ReservationOrganization;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $url;
    protected $requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];
    protected $attributes;
    protected $id;

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/schedule-usage/update/{$id}";
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setIdAndUrl(5);
        $this->attributes = [
            'name' => '通常練習',
            'is_public' => true,
            'reservation_organization_id' => ReservationOrganization::factory()->create()->id,
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
        $existingRecord = ScheduleUsage::factory()->create([
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

    public function testIsPublicEmpty()
    {
        $this->attributes['is_public'] = null;

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'is_public' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testIsPublicNotBoolean()
    {
        $this->attributes['is_public'] = 'abc';

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'is_public' => [
                    __('validation.boolean')
                ]
            ]
        );
    }

    public function testReservationOrganizationIdEmpty()
    {
        $this->attributes['reservation_organization_id'] = null;

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'reservation_organization_id' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testReservationOrganizationIdNotExists()
    {
        $this->attributes['reservation_organization_id'] = ReservationOrganization::max('id') + 100;

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->attributes);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'reservation_organization_id' => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testSuccess()
    {
        $this->mock(ScheduleMasterService::class, function ($mock) {
            $mock->shouldReceive('updateScheduleUsage')
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
        $existingRecord = ScheduleUsage::factory()->create($this->attributes);
        $this->setIdAndUrl($existingRecord->id);
        $this->mock(ScheduleMasterService::class, function ($mock) {
            $mock->shouldReceive('updateScheduleUsage')
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
