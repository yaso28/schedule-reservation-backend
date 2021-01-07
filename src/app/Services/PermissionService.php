<?php

namespace App\Services;

use App\Repositories\UserPermissionRepository;

class PermissionService
{
    protected $userPermissionRepository;

    public function __construct(UserPermissionRepository $userPermissionRepository)
    {
        $this->userPermissionRepository = $userPermissionRepository;
    }

    public function getUserPermissions($user)
    {
        return $this->userPermissionRepository->getUserPermissions($user);
    }
}
