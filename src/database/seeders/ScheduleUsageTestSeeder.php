<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ScheduleUsage as Master;
use App\Models\ReservationOrganization as Related;
use stdClass;

class ScheduleUsageTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ReservationOrganizationTestSeeder::class);

        $orgs = Related::orderBy('id')->take(2)->get();
        $orgGeneral = $orgs[0];
        $orgBeginner = $orgs[1];

        $createTestData = function ($name, $isPublic, $reservationOrganization) {
            $data = new stdClass;
            $data->name = $name;
            $data->isPublic = $isPublic;
            $data->reservationOrganization = $reservationOrganization;
            return $data;
        };
        $testDataList = [
            $createTestData('初心者練習', true, $orgBeginner),
            $createTestData('上級者練習', true, $orgGeneral),
            $createTestData('体験会', true, $orgBeginner),
            $createTestData('会議', false, $orgGeneral),
        ];
        $testDataLength = count($testDataList);
        for ($i = 0; $i < $testDataLength; $i++) {
            $testData = $testDataList[$i];
            Master::factory()->create([
                'name' => $testData->name,
                'is_public' => $testData->isPublic,
                'reservation_organization_id' => $testData->reservationOrganization->id,
                'order_reverse' => $testDataLength - $i - 1,
            ]);
        }
    }
}
