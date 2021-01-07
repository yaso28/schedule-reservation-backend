<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ScheduleStatus as Master;
use App\Models\ScheduleStatus;

class ScheduleStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Master::updateOrCreate(
            ['name' => '未定'],
            [
                'display_type' => ScheduleStatus::DISPLAY_TYPE_WARNING,
                'is_public' => true,
                'bulk_change_mode' => ScheduleStatus::BULK_CHANGE_FROM,
                'order_reverse' => 3,
            ]
        );
        Master::updateOrCreate(
            ['name' => '確定'],
            [
                'display_type' => null,
                'is_public' => true,
                'bulk_change_mode' => ScheduleStatus::BULK_CHANGE_TO,
                'order_reverse' => 2,
            ]
        );
        Master::updateOrCreate(
            ['name' => '中止'],
            [
                'display_type' => ScheduleStatus::DISPLAY_TYPE_DANGER,
                'is_public' => true,
                'bulk_change_mode' => ScheduleStatus::BULK_CHANGE_NONE,
                'order_reverse' => 1,
            ]
        );
        Master::updateOrCreate(
            ['name' => '非公開'],
            [
                'display_type' => null,
                'is_public' => false,
                'bulk_change_mode' => ScheduleStatus::BULK_CHANGE_NONE,
                'order_reverse' => 0,
            ]
        );
    }
}
