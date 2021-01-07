<?php

namespace Tests\Feature\Http\Setting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Permission;
use App\Services\SettingService;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $url;
    protected $category;
    protected $key;
    protected $postData = [
        'value' => 'kdsoihdsdf',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareCategoryDbRecords();
    }

    protected function setUrl($category, $key = 'dummy')
    {
        $this->category = $category;
        $this->key = $key;
        $this->url = "/api/setting/update/${category}/${key}";
    }

    protected function assertHttpFail($makeResponse, $expectedStatusCode)
    {
        $makeResponse()->assertStatus($expectedStatusCode);
    }

    protected function assertHttpSuccess($makeResponse)
    {
        $this->mock(SettingService::class, function ($mock) {
            $mock->shouldReceive('updateValue')
                ->once()
                ->with($this->category, $this->key, $this->postData['value']);
        });
        $makeResponse()->assertStatus(204);
    }

    public function categoryPermissionsProvider()
    {
        return [
            [Category::RESERVATION, [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE]],
            [Category::RESERVATION_PUBLIC, [Permission::RESERVATION_READ, Permission::RESERVATION_WRITE]],
        ];
    }

    /**
     * @dataProvider categoryPermissionsProvider
     */
    public function testNoLogin($category, $permissions)
    {
        $this->setUrl($category);
        $this->assertHttpFail(
            fn () => $this->postJson($this->url, $this->postData),
            401
        );
    }

    /**
     * @dataProvider categoryPermissionsProvider
     */
    public function testNoPermission($category, $permissions)
    {
        $this->setUrl($category);
        $this->assertHttpFail(
            fn () => $this->actingAs($this->createUser())->postJson($this->url, $this->postData),
            403
        );
    }

    /**
     * @dataProvider categoryPermissionsProvider
     */
    public function testPermissionReadOnly($category, $permissions)
    {
        $this->setUrl($category);
        $makeResponse = fn () => $this->actingAs($this->createUser([$permissions[0]]))
            ->postJson($this->url, $this->postData);
        if (count($permissions) < 2) {
            $this->assertHttpSuccess($makeResponse);
        } else {
            $this->assertHttpFail($makeResponse, 403);
        }
    }

    protected function assertAdequatePermissions($category, $permissions)
    {
        $this->setUrl($category);
        $this->assertHttpSuccess(
            fn () => $this->actingAs($this->createUser($permissions))
                ->postJson($this->url, $this->postData)
        );
    }

    /**
     * @dataProvider categoryPermissionsProvider
     */
    public function testValueNull($category, $permissions)
    {
        $this->postData['value'] = null;
        $this->assertAdequatePermissions($category, $permissions);
    }

    /**
     * @dataProvider categoryPermissionsProvider
     */
    public function testValueEmpty($category, $permissions)
    {
        $this->postData['value'] = '';
        $this->assertAdequatePermissions($category, $permissions);
    }

    /**
     * @dataProvider categoryPermissionsProvider
     */
    public function testSuccess($category, $permissions)
    {
        $this->assertAdequatePermissions($category, $permissions);
    }
}
