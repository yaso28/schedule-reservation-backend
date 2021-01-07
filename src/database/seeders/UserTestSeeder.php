<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RoleTestSeeder::class);

        for ($i = 1; $i <= 3; $i++) {
            $user = $this->save("user${i}");

            $user = $this->save("reserve${i}");
            $user->assignRole([RoleTestSeeder::RESERVATION_MEMBER]);

            $user = $this->save("reserve-admin${i}");
            $user->assignRole([RoleTestSeeder::RESERVATION_MEMBER, RoleTestSeeder::RESERVATION_ADMIN]);
        }
    }

    protected function save($localPart)
    {
        return User::factory()->create([
            'email' => "{$localPart}@test",
            'password' => Hash::make("{$localPart}$28"),
        ]);
    }
}
