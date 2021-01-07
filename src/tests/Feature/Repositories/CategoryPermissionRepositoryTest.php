<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Repositories\CategoryPermissionRepository;
use App\Models\CategoryPermission;
use App\Models\Category;

class CategoryPermissionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function getRepository()
    {
        return resolve(CategoryPermissionRepository::class);
    }

    protected function assertSelectCategoryPermissionList($readOnly)
    {
        $categoryName = Category::factory()->create()->name;
        $createRecord = fn ($isReadOnly) => CategoryPermission::factory()->create([
            'category_name' => $categoryName,
            'read_only' => $isReadOnly,
        ]);
        $record1 = $createRecord(true);
        $record2 = $createRecord(false);
        $record3 = $createRecord(true);
        $expected = collect($readOnly ? [$record1, $record3] : [$record1, $record2, $record3]);
        $actual = $this->getRepository()->selectCategoryPermissionList($categoryName, $readOnly);

        $compare = fn ($record1, $record2) => strcmp($record1->permission_name, $record2->permission_name);
        $this->assertDbRecordEquals(
            $expected->sort($compare)->values(),
            $actual->sort($compare)->values()
        );
    }

    public function testSelectCategoryPermissionList_ReadOnly()
    {
        $this->assertSelectCategoryPermissionList(true);
    }

    public function testSelectCategoryPermissionList_NotReadOnly()
    {
        $this->assertSelectCategoryPermissionList(false);
    }
}
