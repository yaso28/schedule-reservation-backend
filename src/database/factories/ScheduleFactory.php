<?php

namespace Database\Factories;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Utilities\DataHelper;
use App\Models\SchedulePlace;
use App\Models\ScheduleUsage;
use App\Models\ScheduleTimetable;
use App\Models\ReservationStatus;
use App\Models\ScheduleStatus;

class ScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $hourFrom = $this->faker->numberBetween(9, 13);
        $hourTo = $hourFrom + $this->faker->numberBetween(3, 6);

        return [
            'ymd' => fn () => DataHelper::randomDate(),
            'begins_at' => fn () => DataHelper::randomTime($hourFrom, $hourFrom),
            'ends_at' => fn () => DataHelper::randomTime($hourTo, $hourTo),
            'schedule_place_id' => fn () => SchedulePlace::factory(),
            'schedule_usage_id' => fn () => ScheduleUsage::factory(),
            'schedule_timetable_id' => fn () => ScheduleTimetable::factory(),
            'reservation_status_id' => fn () => ReservationStatus::factory(),
            'schedule_status_id' => fn () => ScheduleStatus::factory(),
        ];
    }
}
