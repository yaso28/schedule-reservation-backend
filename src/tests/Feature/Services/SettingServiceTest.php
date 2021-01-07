<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\SettingService;
use App\Repositories\SettingRepository;
use App\Models\Setting;
use Hamcrest\Matchers;

class SettingServiceTest extends TestCase
{
    use WithFaker;

    protected $mockCategoryName;
    protected $mockKeyName;
    protected $mockValue;
    protected $mockRecord;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockCategoryName = $this->faker->word;
        $this->mockKeyName = $this->faker->word;
        $this->mockValue = $this->faker->word;
        $this->mockRecord = Setting::factory()->make([
            'category_name' => $this->mockCategoryName,
            'key_name' => $this->mockKeyName,
        ]);
    }

    protected function getService()
    {
        return resolve(SettingService::class);
    }

    public function testGet()
    {
        $this->mock(SettingRepository::class, function ($mock) {
            $mock->shouldReceive('selectSetting')
                ->once()
                ->with($this->mockCategoryName, $this->mockKeyName)
                ->andReturn($this->mockRecord);
        });
        $this->assertEquals(
            $this->mockRecord,
            $this->getService()->get($this->mockCategoryName, $this->mockKeyName)
        );
    }

    public function testUpdateValue()
    {
        $this->mock(SettingRepository::class, function ($mock) {
            $mock->shouldReceive('updateSetting')
                ->once()
                ->with(
                    $this->mockCategoryName,
                    $this->mockKeyName,
                    Matchers::equalTo(['value' => $this->mockValue])
                )
                ->andReturn($this->mockRecord);
        });
        $this->getService()->updateValue($this->mockCategoryName, $this->mockKeyName, $this->mockValue);
    }
}
