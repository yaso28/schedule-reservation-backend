<?php

namespace App\Repositories;

class UserPermissionRepository extends RepositoryBase
{
    public function getUserPermissions($user)
    {
        return $user->getAllPermissions();
    }
}
