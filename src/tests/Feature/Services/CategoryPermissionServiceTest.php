<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\CategoryPermissionService;
use App\Repositories\CategoryPermissionRepository;
use App\Models\CategoryPermission;
use stdClass;

class CategoryPermissionServiceTest extends TestCase
{
    use WithFaker;

    protected function getService()
    {
        return resolve(CategoryPermissionService::class);
    }

    public function testGetPermissionListForCategory()
    {
        $mockData = new stdClass;
        $mockData->categoryName = $this->faker->word;
        $mockData->readOnly = $this->faker->boolean();
        $mockData->permissionNameList = collect([]);
        for ($i = 0; $i < 3; $i++) {
            $mockData->permissionNameList->add($this->faker->word);
        }
        $mockData->recordList = $mockData->permissionNameList->map(
            fn ($permissionName) => CategoryPermission::factory()->make([
                'category_name' => $mockData->categoryName,
                'permission_name' => $permissionName,
            ])
        );

        $this->mock(CategoryPermissionRepository::class, function ($mock) use ($mockData) {
            $mock->shouldReceive('selectCategoryPermissionList')
                ->once()
                ->with($mockData->categoryName, $mockData->readOnly)
                ->andReturn($mockData->recordList);
        });

        $this->assertEquals(
            $mockData->permissionNameList,
            $this->getService()->getPermissionListForCategory($mockData->categoryName, $mockData->readOnly)
        );
    }
}
