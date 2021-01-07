<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Repositories\RepositoryBase;
use App\Models\ScheduleTimetable;

class RepositoryBaseTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function getRepository()
    {
        return new class extends RepositoryBase
        {
        };
    }

    public function testSaveEntity_Insert()
    {
        $record = ScheduleTimetable::factory()->make();
        $actualReturn = $this->getRepository()->saveEntity($record);
        $actualInserted = $this->selectInserted(ScheduleTimetable::class);

        $this->assertDbRecordEquals($record, $actualInserted);
        $this->assertEquals($actualInserted->id, $actualReturn);
    }

    public function testSaveEntity_Update()
    {
        $record = ScheduleTimetable::factory()->create();
        $record->order_reverse = $this->faker->numberBetween(0, 3);
        $actualReturn = $this->getRepository()->saveEntity($record);
        $actualUpdated = ScheduleTimetable::find($record->id);

        $this->assertDbRecordEquals($record, $actualUpdated);
        $this->assertEquals($actualUpdated->id, $actualReturn);
    }

    public function testSaveEntityList()
    {
        $recordToInsert = ScheduleTimetable::factory()->make();
        $recordToUpdate = ScheduleTimetable::factory()->create();
        $recordToUpdate->order_reverse = $this->faker->numberBetween(0, 3);
        $this->getRepository()->saveEntityList([$recordToInsert, $recordToUpdate]);

        $actualInserted = $this->selectInserted(ScheduleTimetable::class);
        $actualUpdated = ScheduleTimetable::find($recordToUpdate->id);

        $this->assertDbRecordEquals($recordToInsert, $actualInserted);
        $this->assertDbRecordEquals($recordToUpdate, $actualUpdated);
    }
}
