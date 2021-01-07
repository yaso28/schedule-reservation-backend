<?php

namespace Database\Factories;

use App\Models\ScheduleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Utilities\DataHelper;

class ScheduleStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduleStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fn () => $this->faker->unique()->city(),
            'display_type' => fn () => $this->faker->randomElement([ScheduleStatus::DISPLAY_TYPE_WARNING, ScheduleStatus::DISPLAY_TYPE_DANGER, null]),
            'is_public' => fn () => $this->faker->boolean(),
            'bulk_change_mode' => fn () => $this->faker->randomElement([ScheduleStatus::BULK_CHANGE_NONE, ScheduleStatus::BULK_CHANGE_FROM, ScheduleStatus::BULK_CHANGE_TO]),
            'order_reverse' => fn () => 0,
        ];
    }
}
