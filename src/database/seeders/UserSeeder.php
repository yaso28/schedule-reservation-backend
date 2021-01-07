<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RoleSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => config('account.admin.id')],
            [
                'name' => 'システム管理者',
                'password' => Hash::make(config('account.admin.password')),
            ]
        );
        $admin->syncRoles(Role::all());
    }
}
