<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleTestSeeder extends Seeder
{
    public const RESERVATION_MEMBER = '予約メンバー';
    public const RESERVATION_ADMIN = '予約管理者';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $order = 1;

        $role = $this->save(self::RESERVATION_ADMIN, $order++);
        $role->givePermissionTo([Permission::RESERVATION_READ, Permission::RESERVATION_WRITE]);

        $role = $this->save(self::RESERVATION_MEMBER, $order++);
        $role->givePermissionTo([Permission::RESERVATION_READ]);
    }

    protected function save($name, $orderReverse)
    {
        return Role::updateOrCreate(
            ['name' => $name],
            [
                'guard_name' => 'web',
                'order_reverse' => $orderReverse,
            ]
        );
    }
}
