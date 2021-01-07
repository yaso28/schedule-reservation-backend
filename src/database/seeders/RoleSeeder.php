<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PermissionSeeder::class);

        $guard = 'web';

        $admin = Role::updateOrCreate(
            ['name' => 'システム管理者'],
            [
                'guard_name' => $guard,
                'order_reverse' => 0,
            ]
        );
        $admin->syncPermissions(Permission::all());
    }
}
