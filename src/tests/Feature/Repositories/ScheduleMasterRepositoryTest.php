<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Repositories\ScheduleMasterRepository;
use App\Models\ScheduleStatus;
use App\Models\ReservationStatus;
use App\Models\ReservationOrganization;
use App\Models\SchedulePlace;
use App\Models\ScheduleUsage;
use App\Models\ScheduleTimetable;

class ScheduleMasterRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function getRepository()
    {
        return resolve(ScheduleMasterRepository::class);
    }

    public function testSelectAllScheduleStatus()
    {
        $this->testMasterSelectAll(
            ScheduleStatus::class,
            fn () => $this->getRepository()->selectAllScheduleStatus()
        );
    }

    public function testSelectInitialScheduleStatus()
    {
        $createRecord = fn ($orderReverse) => ScheduleStatus::factory()->create(['order_reverse' => $orderReverse]);
        $createRecord(2);
        $createRecord(0);
        $expected = $createRecord(3);
        $createRecord(3);
        $createRecord(1);

        $this->assertDbRecordEquals($expected, $this->getRepository()->selectInitialScheduleStatus());
    }

    public function testSelectFixedScheduleStatus()
    {
        $createRecord = fn ($bulkChangeMode, $orderReverse) => ScheduleStatus::factory()->create([
            'bulk_change_mode' => $bulkChangeMode,
            'order_reverse' => $orderReverse
        ]);
        $createRecord(ScheduleStatus::BULK_CHANGE_NONE, 1);
        $createRecord(ScheduleStatus::BULK_CHANGE_TO, 1);
        $createRecord(ScheduleStatus::BULK_CHANGE_NONE, 2);
        $createRecord(ScheduleStatus::BULK_CHANGE_FROM, 2);
        $expected = $createRecord(ScheduleStatus::BULK_CHANGE_TO, 2);
        $createRecord(ScheduleStatus::BULK_CHANGE_TO, 2);
        $createRecord(ScheduleStatus::BULK_CHANGE_FROM, 1);

        $this->assertDbRecordEquals($expected, $this->getRepository()->selectFixedScheduleStatus());
    }

    public function testSelectAllReservationStatus()
    {
        $this->testMasterSelectAll(
            ReservationStatus::class,
            fn () => $this->getRepository()->selectAllReservationStatus()
        );
    }

    public function testSelectInitialReservationStatus()
    {
        $createRecord = fn ($orderReverse) => ReservationStatus::factory()->create(['order_reverse' => $orderReverse]);
        $createRecord(2);
        $createRecord(0);
        $expected = $createRecord(3);
        $createRecord(3);
        $createRecord(1);

        $this->assertDbRecordEquals($expected, $this->getRepository()->selectInitialReservationStatus());
    }

    public function testSelectAllReservationOrganization()
    {
        $this->testMasterSelectAll(
            ReservationOrganization::class,
            fn () => $this->getRepository()->selectAllReservationOrganization()
        );
    }

    public function testSelectReservationOrganization()
    {
        $expected = ReservationOrganization::factory()->create();
        $actual = $this->getRepository()->selectReservationOrganization($expected->id);
        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testInsertReservationOrganization()
    {
        $attributes = [
            'name' => '団体名称',
            'abbreviation' => '略称',
            'registration_number' => '1234-567890',
        ];

        $actualReturn = $this->getRepository()->insertReservationOrganization($attributes);

        $expectedAdded = $attributes;
        $expectedAdded['order_reverse'] = 0;
        $actualAdded = $this->selectInserted(ReservationOrganization::class);

        $this->assertChangedDbRecord($expectedAdded, $actualAdded);
        $this->assertEquals($actualAdded->id, $actualReturn);
    }

    public function testUpdateReservationOrganization()
    {
        $attributes = [
            'name' => '団体名称',
            'abbreviation' => '略称',
            'registration_number' => '1234-567890',
        ];
        $id = ReservationOrganization::factory()->create()->id;

        $actualReturn = $this->getRepository()->updateReservationOrganization($id, $attributes);

        $this->assertChangedDbRecord($attributes, ReservationOrganization::find($id));
        $this->assertEquals($id, $actualReturn);
    }

    public function testReorderReservationOrganization()
    {
        $this->testMasterReorder(
            ReservationOrganization::class,
            fn ($dataList) => $this->getRepository()->reorderReservationOrganization($dataList)
        );
    }

    public function testSelectAllSchedulePlace()
    {
        $this->testMasterSelectAll(
            SchedulePlace::class,
            fn () => $this->getRepository()->selectAllSchedulePlace()
        );
    }

    public function testSelectSchedulePlace()
    {
        $expected = SchedulePlace::factory()->create();
        $actual = $this->getRepository()->selectSchedulePlace($expected->id);
        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testInsertSchedulePlace()
    {
        $attributes = [
            'name' => '会議室＠公民館',
            'abbreviation' => '会議室',
            'price_per_hour' => 789,
        ];

        $actualReturn = $this->getRepository()->insertSchedulePlace($attributes);

        $expectedAdded = $attributes;
        $expectedAdded['order_reverse'] = 0;
        $actualAdded = $this->selectInserted(SchedulePlace::class);

        $this->assertChangedDbRecord($expectedAdded, $actualAdded);
        $this->assertEquals($actualAdded->id, $actualReturn);
    }

    public function testUpdateSchedulePlace()
    {
        $attributes = [
            'name' => '会議室＠公民館',
            'abbreviation' => '会議室',
            'price_per_hour' => 789,
        ];
        $id = SchedulePlace::factory()->create()->id;

        $actualReturn = $this->getRepository()->updateSchedulePlace($id, $attributes);

        $this->assertChangedDbRecord($attributes, SchedulePlace::find($id));
        $this->assertEquals($id, $actualReturn);
    }

    public function testReorderSchedulePlace()
    {
        $this->testMasterReorder(
            SchedulePlace::class,
            fn ($dataList) => $this->getRepository()->reorderSchedulePlace($dataList)
        );
    }

    public function testSelectAllScheduleUsage()
    {
        $createRecord = function ($orderReverse, $relatedRecord) {
            $record = ScheduleUsage::factory()->create([
                'reservation_organization_id' => $relatedRecord->id,
                'order_reverse' => $orderReverse,
            ]);
            $record->reservation_organization;
            return $record;
        };
        $relatedRecords = ReservationOrganization::factory()->count(2)->create();
        $record1 = $createRecord(1, $relatedRecords[0]);
        $record2 = $createRecord(2, $relatedRecords[0]);
        $record3 = $createRecord(0, $relatedRecords[1]);
        $record4 = $createRecord(0, $relatedRecords[0]);
        $record5 = $createRecord(1, $relatedRecords[1]);

        $actual = $this->getRepository()->selectAllScheduleUsage();

        $this->assertDbRecordEquals(
            [$record2, $record1, $record5, $record3, $record4],
            $actual
        );
    }

    public function testSelectScheduleUsage()
    {
        $expected = ScheduleUsage::factory()->create();
        $expected->reservation_organization;

        $actual = $this->getRepository()->selectScheduleUsage($expected->id);
        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testInsertScheduleUsage()
    {
        $attributes = [
            'name' => '団体名称',
            'is_public' => true,
            'reservation_organization_id' => ReservationOrganization::factory()->create()->id,
        ];

        $actualReturn = $this->getRepository()->insertScheduleUsage($attributes);

        $expectedAdded = $attributes;
        $expectedAdded['order_reverse'] = 0;
        $actualAdded = $this->selectInserted(ScheduleUsage::class);
        $this->formatRecordBool($actualAdded, 'is_public');

        $this->assertChangedDbRecord($expectedAdded, $actualAdded);
        $this->assertEquals($actualAdded->id, $actualReturn);
    }

    public function testUpdateScheduleUsage()
    {
        $attributes = [
            'name' => '団体名称',
            'is_public' => true,
            'reservation_organization_id' => ReservationOrganization::factory()->create()->id,
        ];
        $id = ScheduleUsage::factory()->create()->id;

        $actualReturn = $this->getRepository()->updateScheduleUsage($id, $attributes);
        $actualUpdated = ScheduleUsage::find($id);
        $this->formatRecordBool($actualUpdated, 'is_public');

        $this->assertChangedDbRecord($attributes, $actualUpdated);
        $this->assertEquals($id, $actualReturn);
    }

    public function testReorderScheduleUsage()
    {
        $this->testMasterReorder(
            ScheduleUsage::class,
            fn ($dataList) => $this->getRepository()->reorderScheduleUsage($dataList)
        );
    }

    public function testSelectAllScheduleTimetable()
    {
        $this->testMasterSelectAll(
            ScheduleTimetable::class,
            fn () => $this->getRepository()->selectAllScheduleTimetable()
        );
    }

    public function testSelectScheduleTimetable()
    {
        $expected = ScheduleTimetable::factory()->create();
        $actual = $this->getRepository()->selectScheduleTimetable($expected->id);
        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testInsertScheduleTimetable()
    {
        $attributes = [
            'name' => '午前',
            'details' => "10:00-\n11:00-",
        ];

        $actualReturn = $this->getRepository()->insertScheduleTimetable($attributes);

        $expectedAdded = $attributes;
        $expectedAdded['order_reverse'] = 0;
        $actualAdded = $this->selectInserted(ScheduleTimetable::class);

        $this->assertChangedDbRecord($expectedAdded, $actualAdded);
        $this->assertEquals($actualAdded->id, $actualReturn);
    }

    public function testUpdateScheduleTimetable()
    {
        $attributes = [
            'name' => '午前',
            'details' => "10:00-\n11:00-",
        ];
        $actualReturn = $id = ScheduleTimetable::factory()->create()->id;

        $this->getRepository()->updateScheduleTimetable($id, $attributes);

        $this->assertChangedDbRecord($attributes, ScheduleTimetable::find($id));
        $this->assertEquals($id, $actualReturn);
    }

    public function testReorderScheduleTimetable()
    {
        $this->testMasterReorder(
            ScheduleTimetable::class,
            fn ($dataList) => $this->getRepository()->reorderScheduleTimetable($dataList)
        );
    }
}
