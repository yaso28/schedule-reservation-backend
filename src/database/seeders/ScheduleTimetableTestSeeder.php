<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ScheduleTimetable as Master;
use stdClass;

class ScheduleTimetableTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createTestData = function ($name, $details) {
            $data = new stdClass;
            $data->name = $name;
            $data->details = $details;
            return $data;
        };
        $testDataList = [
            $createTestData('午前', "1回目：10:00-\n2回目：11:30-"),
            $createTestData('午後', "1回目：13:00-\n2回目：14:30-"),
            $createTestData('一日', "1回目：10:00-\n2回目：11:30-\n3回目：13:00-\n4回目：14:30-"),
        ];
        $testDataLength = count($testDataList);
        for ($i = 0; $i < $testDataLength; $i++) {
            $testData = $testDataList[$i];
            Master::updateOrCreate(
                ['name' => $testData->name],
                [
                    'details' => $testData->details,
                    'order_reverse' => $testDataLength - $i - 1,
                ]
            );
        }
    }
}
