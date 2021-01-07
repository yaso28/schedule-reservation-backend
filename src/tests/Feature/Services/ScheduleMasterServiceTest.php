<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\ScheduleMasterService;
use App\Repositories\ScheduleMasterRepository;
use stdClass;

class ScheduleMasterServiceTest extends TestCase
{
    protected $mockCollection;
    protected $mockRecord;
    protected $mockAttributes;
    protected $mockId;
    protected $mockIdList;
    protected $mockOrderReverseDataList;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockCollection = collect([1, 2, 3]);
        $this->mockRecord = new stdClass;
        $this->mockAttributes = ['name' => 'abc', 'abbreviation' => 'def'];
        $this->mockId = 3;
        $this->mockIdList = [3, 5, 1, 2, 4];
        $this->mockOrderReverseDataList = [
            ['id' => 3, 'order_reverse' => 5],
            ['id' => 5, 'order_reverse' => 4],
            ['id' => 1, 'order_reverse' => 3],
            ['id' => 2, 'order_reverse' => 2],
            ['id' => 4, 'order_reverse' => 1],
        ];
    }

    protected function getService()
    {
        return resolve(ScheduleMasterService::class);
    }

    public function testGetScheduleStatusList()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectAllScheduleStatus')
                ->once()
                ->withNoArgs()
                ->andReturn($this->mockCollection);
        });

        $this->assertEquals(
            $this->mockCollection,
            $this->getService()->getScheduleStatusList()
        );
    }

    public function testGetInitialScheduleStatus()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectInitialScheduleStatus')
                ->once()
                ->withNoArgs()
                ->andReturn($this->mockRecord);
        });

        $service = $this->getService();
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                $this->mockRecord,
                $service->getInitialScheduleStatus()
            );
        }
    }

    public function testGetFixedScheduleStatus()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectFixedScheduleStatus')
                ->once()
                ->withNoArgs()
                ->andReturn($this->mockRecord);
        });

        $service = $this->getService();
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                $this->mockRecord,
                $service->getFixedScheduleStatus()
            );
        }
    }

    public function testGetReservationStatusList()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectAllReservationStatus')
                ->once()
                ->withNoArgs()
                ->andReturn($this->mockCollection);
        });

        $this->assertEquals(
            $this->mockCollection,
            $this->getService()->getReservationStatusList()
        );
    }

    public function testGetInitialReservationStatus()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectInitialReservationStatus')
                ->once()
                ->withNoArgs()
                ->andReturn($this->mockRecord);
        });

        $service = $this->getService();
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(
                $this->mockRecord,
                $service->getInitialReservationStatus()
            );
        }
    }

    public function testGetReservationOrganizationList()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectAllReservationOrganization')
                ->once()
                ->withNoArgs()
                ->andReturn($this->mockCollection);
        });

        $this->assertEquals(
            $this->mockCollection,
            $this->getService()->getReservationOrganizationList()
        );
    }

    public function testGetReservationOrganization()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectReservationOrganization')
                ->once()
                ->with($this->mockId)
                ->andReturn($this->mockRecord);
        });

        $this->assertEquals(
            $this->mockRecord,
            $this->getService()->getReservationOrganization($this->mockId)
        );
    }

    public function testAddReservationOrganization()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('insertReservationOrganization')
                ->once()
                ->with($this->mockAttributes)
                ->andReturn($this->mockId);
        });

        $actual = $this->getService()->addReservationOrganization($this->mockAttributes);

        $this->assertEquals($this->mockId, $actual);
    }

    public function testUpdateReservationOrganization()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('updateReservationOrganization')
                ->once()
                ->with($this->mockId, $this->mockAttributes)
                ->andReturn($this->mockId);
        });

        $actual = $this->getService()->updateReservationOrganization($this->mockId, $this->mockAttributes);

        $this->assertEquals($this->mockId, $actual);
    }

    public function testReorderReservationOrganization()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('reorderReservationOrganization')
                ->once()
                ->with($this->mockOrderReverseDataList);
        });

        $this->getService()->reorderReservationOrganization($this->mockIdList);
    }

    public function testGetSchedulePlaceList()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectAllSchedulePlace')
                ->once()
                ->withNoArgs()
                ->andReturn($this->mockCollection);
        });

        $this->assertEquals(
            $this->mockCollection,
            $this->getService()->getSchedulePlaceList()
        );
    }

    public function testGetSchedulePlace()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectSchedulePlace')
                ->once()
                ->with($this->mockId)
                ->andReturn($this->mockRecord);
        });

        $this->assertEquals(
            $this->mockRecord,
            $this->getService()->getSchedulePlace($this->mockId)
        );
    }

    public function testAddSchedulePlace()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('insertSchedulePlace')
                ->once()
                ->with($this->mockAttributes)
                ->andReturn($this->mockId);
        });

        $actual = $this->getService()->addSchedulePlace($this->mockAttributes);

        $this->assertEquals($this->mockId, $actual);
    }

    public function testUpdateSchedulePlace()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('updateSchedulePlace')
                ->once()
                ->with($this->mockId, $this->mockAttributes)
                ->andReturn($this->mockId);
        });

        $actual = $this->getService()->updateSchedulePlace($this->mockId, $this->mockAttributes);

        $this->assertEquals($this->mockId, $actual);
    }

    public function testReorderSchedulePlace()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('reorderSchedulePlace')
                ->once()
                ->with($this->mockOrderReverseDataList);
        });

        $this->getService()->reorderSchedulePlace($this->mockIdList);
    }

    public function testGetScheduleUsageList()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectAllScheduleUsage')
                ->once()
                ->withNoArgs()
                ->andReturn($this->mockCollection);
        });

        $this->assertEquals(
            $this->mockCollection,
            $this->getService()->getScheduleUsageList()
        );
    }

    public function testGetScheduleUsage()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectScheduleUsage')
                ->once()
                ->with($this->mockId)
                ->andReturn($this->mockRecord);
        });

        $this->assertEquals(
            $this->mockRecord,
            $this->getService()->getScheduleUsage($this->mockId)
        );
    }

    public function testAddScheduleUsage()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('insertScheduleUsage')
                ->once()
                ->with($this->mockAttributes)
                ->andReturn($this->mockId);
        });

        $actual = $this->getService()->addScheduleUsage($this->mockAttributes);

        $this->assertEquals($this->mockId, $actual);
    }

    public function testUpdateScheduleUsage()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('updateScheduleUsage')
                ->once()
                ->with($this->mockId, $this->mockAttributes)
                ->andReturn($this->mockId);
        });

        $actual = $this->getService()->updateScheduleUsage($this->mockId, $this->mockAttributes);

        $this->assertEquals($this->mockId, $actual);
    }

    public function testReorderScheduleUsage()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('reorderScheduleUsage')
                ->once()
                ->with($this->mockOrderReverseDataList);
        });

        $this->getService()->reorderScheduleUsage($this->mockIdList);
    }

    public function testGetScheduleTimetableList()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectAllScheduleTimetable')
                ->once()
                ->withNoArgs()
                ->andReturn($this->mockCollection);
        });

        $this->assertEquals(
            $this->mockCollection,
            $this->getService()->getScheduleTimetableList()
        );
    }

    public function testGetScheduleTimetable()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('selectScheduleTimetable')
                ->once()
                ->with($this->mockId)
                ->andReturn($this->mockRecord);
        });

        $this->assertEquals(
            $this->mockRecord,
            $this->getService()->getScheduleTimetable($this->mockId)
        );
    }

    public function testAddScheduleTimetable()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('insertScheduleTimetable')
                ->once()
                ->with($this->mockAttributes)
                ->andReturn($this->mockId);
        });

        $actual = $this->getService()->addScheduleTimetable($this->mockAttributes);

        $this->assertEquals($this->mockId, $actual);
    }

    public function testUpdateScheduleTimetable()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('updateScheduleTimetable')
                ->once()
                ->with($this->mockId, $this->mockAttributes)
                ->andReturn($this->mockId);
        });

        $actual = $this->getService()->updateScheduleTimetable($this->mockId, $this->mockAttributes);

        $this->assertEquals($this->mockId, $actual);
    }

    public function testReorderScheduleTimetable()
    {
        $this->mock(ScheduleMasterRepository::class, function ($mock) {
            $mock->shouldReceive('reorderScheduleTimetable')
                ->once()
                ->with($this->mockOrderReverseDataList);
        });

        $this->getService()->reorderScheduleTimetable($this->mockIdList);
    }
}
