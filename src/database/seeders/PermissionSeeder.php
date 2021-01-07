<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $order = 0;

        $this->save(Permission::RESERVATION_WRITE, $order++);
        $this->save(Permission::RESERVATION_READ, $order++);
    }

    protected function save($name, $orderReverse)
    {
        Permission::updateOrCreate(
            ['name' => $name],
            [
                'guard_name' => 'web',
                'order_reverse' => $orderReverse,
            ]
        );
    }
}
