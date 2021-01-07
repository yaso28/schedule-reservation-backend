<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            CategoryPermissionSeeder::class,
            SettingSeeder::class,
            ScheduleSeeder::class,
        ]);
    }
}
