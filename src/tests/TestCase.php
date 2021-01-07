<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\Role;
use App\Models\User;
use App\Services\FormatService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\CategoryPermissionSeeder;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private $preparedPermissionDbRecords;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparedPermissionDbRecords = false;
    }

    protected function partialMockWithArgs($abstract, $args, $closure)
    {
        $this->instance($abstract, Mockery::mock($abstract, $args, $closure)->makePartial());
    }

    protected function preparePermissionDbRecords()
    {
        if ($this->preparedPermissionDbRecords) {
            return;
        }
        $this->seed(PermissionSeeder::class);
        $this->preparedPermissionDbRecords = true;
    }

    protected function prepareCategoryDbRecords()
    {
        $this->preparePermissionDbRecords();
        $this->seed(CategoryPermissionSeeder::class);
    }

    protected function createUser($permissions = [])
    {
        $user = User::factory()->create();
        if (count($permissions) > 0) {
            $this->preparePermissionDbRecords();
            $role = Role::factory()->create();
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        }
        return $user;
    }

    protected function assertResponseContent($response, $key, $expectedContent, $message = '')
    {
        $this->assertEquals(
            $expectedContent,
            json_decode($response->content(), true)[$key],
            $message
        );
    }

    protected function formatRecordBool($record, $column)
    {
        $record[$column] = resolve(FormatService::class)->bool($record[$column]);
    }

    protected function formatRecordTime($record, $column)
    {
        $record[$column] = resolve(FormatService::class)->time($record[$column]);
    }

    protected function selectInserted($model)
    {
        return $model::orderBy('id', 'desc')->first();
    }

    protected function assertChangedDbRecord($expectedAttributes, $actualRecord, $message = '')
    {
        $actualAttributes = $actualRecord->toArray();
        foreach ($expectedAttributes as $key => $value) {
            $this->assertArrayHasKey($key, $actualAttributes, "{$message}[{$key}]: key exists");
            $this->assertSame($value, $actualAttributes[$key], "{$message}[{$key}]: equals");
        }
    }

    protected function assertDbRecordEquals($expected, $actual, $message = '')
    {
        $dbRecordsToArray = fn ($records) => collect($records)->toArray();
        $this->assertEquals(
            $dbRecordsToArray($expected),
            $dbRecordsToArray($actual),
            $message
        );
    }

    protected function createMasterRecord($model, $orderReverse)
    {
        return $model::factory()->create([
            'order_reverse' => $orderReverse
        ]);
    }

    protected function testMasterSelectAll($model, $actualClosure)
    {
        $record1 = $this->createMasterRecord($model, 1);
        $record2 = $this->createMasterRecord($model, 0);
        $record3 = $this->createMasterRecord($model, 2);
        $record4 = $this->createMasterRecord($model, 1);

        $actual = $actualClosure();

        $this->assertDbRecordEquals(
            [$record3, $record1, $record4, $record2],
            $actual
        );
    }

    protected function testMasterReorder($model, $actualClosure)
    {
        $recordsCount = 5;
        $idList = $model::factory()->count($recordsCount)->create()
            ->map(fn ($record) => $record->id)->toArray();
        shuffle($idList);
        $dataList = [];
        foreach ($idList as $index => $id) {
            $dataList[] = [
                'id' => $id,
                'order_reverse' => $recordsCount - $index,
            ];
        }

        $actualClosure($dataList);
        $actualResult = $model::select(['id', 'order_reverse'])
            ->orderBy('order_reverse', 'desc')->orderBy('id')
            ->get()->toArray();

        $this->assertEquals($dataList, $actualResult);
    }
}
