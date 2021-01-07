<?php

namespace App\Repositories;

use App\Models\CategoryPermission;

class CategoryPermissionRepository extends RepositoryBase
{
    public function selectCategoryPermissionList($categoryName, $readOnly)
    {
        $query = CategoryPermission::where('category_name', $categoryName);
        if ($readOnly) {
            $query = $query->where('read_only', true);
        }
        return $query->get();
    }
}
