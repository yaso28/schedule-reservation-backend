<?php

namespace App\Services;

use App\Repositories\CategoryPermissionRepository;

class CategoryPermissionService
{
    protected $categoryPermissionRepository;

    public function __construct(CategoryPermissionRepository $categoryPermissionRepository)
    {
        $this->categoryPermissionRepository = $categoryPermissionRepository;
    }

    public function getPermissionListForCategory($categoryName, $readOnly)
    {
        return $this->categoryPermissionRepository->selectCategoryPermissionList($categoryName, $readOnly)
            ->map(fn ($record) => $record->permission_name);
    }
}
