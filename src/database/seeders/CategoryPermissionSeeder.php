<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoryPermission;
use App\Models\Category;
use App\Models\Permission;

class CategoryPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CategorySeeder::class);

        foreach ([
            [Category::RESERVATION, Permission::RESERVATION_READ, true],
            [Category::RESERVATION, Permission::RESERVATION_WRITE, false],
            [Category::RESERVATION_PUBLIC, Permission::RESERVATION_READ, false],
            [Category::RESERVATION_PUBLIC, Permission::RESERVATION_WRITE, false],
        ] as $data) {
            $where = [
                'category_name' => $data[0],
                'permission_name' => $data[1],
            ];
            $values = [
                'read_only' => $data[2],
            ];
            $query = CategoryPermission::query();
            foreach ($where as $column => $condition) {
                $query = $query->where($column, $condition);
            }
            if ($query->count()) {
                $query->update($values);
            } else {
                CategoryPermission::create(array_merge($where, $values));
            }
        }
    }
}
