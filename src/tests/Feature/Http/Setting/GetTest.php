<?php

namespace Tests\Feature\Http\Setting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Permission;
use App\Services\SettingService;

class GetTest extends TestCase
{
    use RefreshDatabase;

    protected $url;
    protected $category;
    protected $key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareCategoryDbRecords();
    }

    protected function setUrl($category, $key = 'dummy')
    {
        $this->category = $category;
        $this->key = $key;
        $this->url = "/api/setting/get/${category}/${key}";
    }

    protected function assertHttpFail($makeResponse)
    {
        $makeResponse()->assertStatus(403);
    }

    protected function assertHttpSuccess($makeResponse)
    {
        $record = Setting::factory()->make();
        $this->mock(SettingService::class, function ($mock) use ($record) {
            $mock->shouldReceive('get')
                ->once()
                ->with($this->category, $this->key)
                ->andReturn($record);
        });
        $makeResponse()->assertStatus(200)
            ->assertJson([
                'data' => [
                    'category_name' => $record->category_name,
                    'key_name' => $record->key_name,
                    'value' => $record->value,
                    'description' => $record->description,
                ],
            ]);
    }

    public function categoryPermissionsProvider()
    {
        return [
            [Category::RESERVATION, [Permission::RESERVATION_READ]],
            [Category::RESERVATION_PUBLIC, []],
        ];
    }

    /**
     * @dataProvider categoryPermissionsProvider
     */
    public function testNoLogin($category, $permissions)
    {
        $this->setUrl($category);
        $makeResponse = fn () => $this->getJson($this->url);
        if (count($permissions) == 0) {
            $this->assertHttpSuccess($makeResponse);
        } else {
            $this->assertHttpFail($makeResponse);
        }
    }

    /**
     * @dataProvider categoryPermissionsProvider
     */
    public function testNoPermission($category, $permissions)
    {
        $this->setUrl($category);
        $makeResponse = fn () => $this->actingAs($this->createUser())
            ->getJson($this->url);
        if (count($permissions) == 0) {
            $this->assertHttpSuccess($makeResponse);
        } else {
            $this->assertHttpFail($makeResponse);
        }
    }

    /**
     * @dataProvider categoryPermissionsProvider
     */
    public function testSuccess($category, $permissions)
    {
        $this->setUrl($category);
        $makeResponse = fn () => $this->actingAs($this->createUser($permissions))
            ->getJson($this->url);
        $this->assertHttpSuccess($makeResponse);
    }
}
