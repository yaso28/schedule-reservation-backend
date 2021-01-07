<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReservationOrganization as Master;
use stdClass;

class ReservationOrganizationTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createTestData = function ($name, $abbreviation) {
            $data = new stdClass;
            $data->name = $name;
            $data->abbreviation = $abbreviation;
            return $data;
        };
        $testDataList = [
            $createTestData('一般部門', '一般'),
            $createTestData('普及活動部門', '普及'),
        ];
        $testDataLength = count($testDataList);
        for ($i = 0; $i < $testDataLength; $i++) {
            $testData = $testDataList[$i];
            Master::factory()->create([
                'name' => $testData->name,
                'abbreviation' => $testData->abbreviation,
                'order_reverse' => $testDataLength - $i - 1,
            ]);
        }
    }
}
