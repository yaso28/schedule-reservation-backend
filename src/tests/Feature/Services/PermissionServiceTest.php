<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\PermissionService;
use App\Models\User;
use App\Models\Permission;
use App\Repositories\UserPermissionRepository;

class PermissionServiceTest extends TestCase
{
    protected function getPermissionService()
    {
        return resolve(PermissionService::class);
    }

    public function testGetUserPermissions()
    {
        $user = User::factory()->make();
        $permissions = Permission::factory()->count(3)->make();

        $this->mock(
            UserPermissionRepository::class,
            function ($mock) use ($user, $permissions) {
                $mock->shouldReceive('getUserPermissions')
                    ->once()
                    ->with($user)
                    ->andReturn($permissions);
            }
        );

        $actual = $this->getPermissionService()->getUserPermissions($user);

        $this->assertEquals($permissions, $actual);
    }
}
