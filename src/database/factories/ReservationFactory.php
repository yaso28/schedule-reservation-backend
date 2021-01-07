<?php

namespace Database\Factories;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Utilities\DataHelper;
use App\Models\Schedule;
use App\Models\ReservationStatus;

class ReservationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Reservation::class;

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
            'schedule_id' => fn () => Schedule::factory(),
            'begins_at' => fn () => DataHelper::randomTime($hourFrom, $hourFrom),
            'ends_at' => fn () => DataHelper::randomTime($hourTo, $hourTo),
            'reservation_status_id' => fn () => ReservationStatus::factory(),
        ];
    }
}
