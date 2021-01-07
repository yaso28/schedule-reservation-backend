<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Repositories\UserPermissionRepository;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use stdClass;

class UserPermissionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function getUserPermissionRepository()
    {
        return resolve(UserPermissionRepository::class);
    }

    protected function provideForGetUserPermissions()
    {
        $permission1 = Permission::factory()->create(['name' => 'p1']);
        $permission2 = Permission::factory()->create(['name' => 'p2']);
        $permission3 = Permission::factory()->create(['name' => 'p3']);

        $roles = Role::factory()->count(2)->create();
        $roles[0]->syncPermissions([$permission2, $permission1]);
        $roles[1]->syncPermissions([$permission1, $permission3]);

        $users = User::factory()->count(3)->create();
        $users[0]->syncRoles([$roles[0]]);
        $users[1]->syncRoles([$roles[0], $roles[1]]);
        $users[2]->syncRoles([]);

        $createData = function ($case, $user, $expected) {
            $result = new stdClass;
            $result->case = $case;
            $result->user = $user;
            $result->expected = $expected;
            return $result;
        };
        return [
            $createData('Role１つ', $users[0], ['p1', 'p2']),
            $createData('Role複数', $users[1], ['p1', 'p2', 'p3']),
            $createData('Role無し', $users[2], []),
        ];
    }

    public function testGetUserPermissions()
    {
        foreach ($this->provideForGetUserPermissions() as $testData) {
            $actual = $this->getUserPermissionRepository()->getUserPermissions($testData->user);
            $actual = $actual
                ->map(fn ($permission) => $permission->name)
                ->sort()
                ->values()
                ->toArray();
            $this->assertEquals($testData->expected, $actual, $testData->case);
        }
    }
}
