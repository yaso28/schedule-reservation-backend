<?php

namespace Database\Factories;

use App\Models\Month;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ReservationStatus;
use App\Models\ScheduleStatus;

class MonthFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Month::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'year' => fn () => now()->year,
            'month' => fn () => $this->faker->numberBetween(1, 12),
            'reservation_status_id' => fn () => ReservationStatus::factory(),
            'schedule_status_id' => fn () => ScheduleStatus::factory(),
        ];
    }
}
