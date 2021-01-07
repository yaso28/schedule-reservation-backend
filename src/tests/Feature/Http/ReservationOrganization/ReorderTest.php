<?php

namespace Tests\Feature\Http\ReservationOrganization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Permission;
use App\Models\ReservationOrganization;
use App\Services\ScheduleMasterService;

class ReorderTest extends TestCase
{
    use RefreshDatabase;

    protected $url;
    protected $requiredPermissions;
    protected $idList;

    protected function makePostData($idList)
    {
        return ['id_list' => $idList];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = '/api/reservation-organization/reorder';
        $this->requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];

        $records = ReservationOrganization::factory()->count(5)->create();
        $this->idList = $records->map(fn ($record) => $record->id)->toArray();
        shuffle($this->idList);
    }

    public function testNoLogin()
    {
        $response = $this->postJson($this->url, $this->makePostData($this->idList));
        $response->assertStatus(401);
    }

    public function testNoPermission()
    {
        $response = $this->actingAs($this->createUser())
            ->postJson($this->url, $this->makePostData($this->idList));
        $response->assertStatus(403);
    }

    public function testPermissionReadOnly()
    {
        $response = $this->actingAs($this->createUser([Permission::RESERVATION_READ]))
            ->postJson($this->url, $this->makePostData($this->idList));
        $response->assertStatus(403);
    }

    public function testIdListNull()
    {
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->makePostData(null));

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'id_list' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testIdListEmptyArray()
    {
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->makePostData([]));

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'id_list' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testIdListNotArray()
    {
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->makePostData('1,2,3'));

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'id_list' => [
                    __('validation.array')
                ]
            ]
        );
    }

    public function testIdListNotDistinct()
    {
        array_push($this->idList, $this->idList[0]);
        $lastIndex = count($this->idList) - 1;

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->makePostData($this->idList));

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'id_list.0' => [
                    __('validation.distinct')
                ],
                "id_list.{$lastIndex}" => [
                    __('validation.distinct')
                ],
            ]
        );
    }

    public function testIdListIncludesNotExists()
    {
        array_push($this->idList, ReservationOrganization::max('id') + 100);
        $lastIndex = count($this->idList) - 1;

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->makePostData($this->idList));

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                "id_list.{$lastIndex}" => [
                    __('validation.exists')
                ]
            ]
        );
    }

    public function testSuccess()
    {
        $this->mock(ScheduleMasterService::class, function ($mock) {
            $mock->shouldReceive('reorderReservationOrganization')
                ->once()
                ->with($this->idList);
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->makePostData($this->idList));

        $response->assertStatus(204);
    }
}
