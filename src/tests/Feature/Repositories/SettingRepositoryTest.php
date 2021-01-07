<?php

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Repositories\SettingRepository;
use App\Models\Setting;

class SettingRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function getRepository()
    {
        return resolve(SettingRepository::class);
    }

    public function testSelectSetting()
    {
        $expected = Setting::factory()->create();
        $actual = $this->getRepository()->selectSetting($expected->category_name, $expected->key_name);
        $this->assertDbRecordEquals($expected, $actual);
    }

    public function testUpdateSetting()
    {
        $record = Setting::factory()->create();
        $categoryName = $record->category_name;
        $keyName = $record->key_name;
        $attributes = [
            'value' => $this->faker->city,
            'description' => $this->faker->realText(40),
        ];

        $this->getRepository()->updateSetting($categoryName, $keyName, $attributes);

        $this->assertChangedDbRecord(
            $attributes,
            Setting::where('category_name', $categoryName)
                ->where('key_name', $keyName)
                ->first()
        );
    }
}
