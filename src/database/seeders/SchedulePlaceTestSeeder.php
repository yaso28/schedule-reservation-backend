<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchedulePlace as Master;
use stdClass;

class SchedulePlaceTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createTestData = function ($name, $abbreviation, $price) {
            $data = new stdClass;
            $data->name = $name;
            $data->abbreviation = $abbreviation;
            $data->price = $price;
            return $data;
        };
        $testDataList = [
            $createTestData('体育館＠公民館別棟', '体育館', 900),
            $createTestData('多目的ホール＠公民館1F', '多目的ホール', 700),
            $createTestData('会議室＠公民館2F', '会議室', 500),
        ];
        $testDataLength = count($testDataList);
        for ($i = 0; $i < $testDataLength; $i++) {
            $testData = $testDataList[$i];
            Master::updateOrCreate(
                ['name' => $testData->name],
                [
                    'abbreviation' => $testData->abbreviation,
                    'price_per_hour' => $testData->price,
                    'order_reverse' => $testDataLength - $i - 1,
                ]
            );
        }
    }
}
