<?php

namespace Tests\Feature\Http\Month;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Permission;
use App\Services\MonthScheduleService;
use Tests\Utilities\DataHelper;

class SendTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $url;
    protected $id;
    protected $requiredPermissions = [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE];
    protected $postData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setIdAndUrl(5);
        $this->postData = [
            'mail_to' => $this->faker->safeEmail,
            'subject' => DataHelper::randomText(),
            'message' => $this->faker->realText(500),
        ];
    }

    protected function setIdAndUrl($id)
    {
        $this->id = $id;
        $this->url = "/api/month/send/{$id}";
    }

    public function testNoLogin()
    {
        $response = $this->postJson($this->url, $this->postData);
        $response->assertStatus(401);
    }

    public function testNoPermission()
    {
        $response = $this->actingAs($this->createUser())
            ->postJson($this->url, $this->postData);
        $response->assertStatus(403);
    }

    public function testPermissionReadOnly()
    {
        $response = $this->actingAs($this->createUser([Permission::RESERVATION_READ]))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(403);
    }

    public function testMailToEmpty()
    {
        $invalidDataKey = 'mail_to';
        $this->postData[$invalidDataKey] = '';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $invalidDataKey => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testMailToNotMailAddress()
    {
        $invalidDataKey = 'mail_to';
        $this->postData[$invalidDataKey] = 'alkjsdgdfj';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $invalidDataKey => [
                    __('validation.email')
                ]
            ]
        );
    }

    public function testSubjectEmpty()
    {
        $invalidDataKey = 'subject';
        $this->postData[$invalidDataKey] = '';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $invalidDataKey => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testMessageEmpty()
    {
        $invalidDataKey = 'message';
        $this->postData[$invalidDataKey] = '';
        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                $invalidDataKey => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testSuccess()
    {
        $this->mock(MonthScheduleService::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->with($this->id, $this->postData)
                ->andReturn($this->id);
        });

        $response = $this->actingAs($this->createUser($this->requiredPermissions))
            ->postJson($this->url, $this->postData);
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->id,
                ],
            ]);
    }
}
