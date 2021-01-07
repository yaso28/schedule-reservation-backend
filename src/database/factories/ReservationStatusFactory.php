<?php

namespace Database\Factories;

use App\Models\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Utilities\DataHelper;

class ReservationStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReservationStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fn () => $this->faker->unique()->city(),
            'description' => fn () => $this->faker->realText(40),
            'reserved' => fn () => true,
            'order_reverse' => fn () => 0,
        ];
    }
}
