<?php

namespace Database\Factories;

use App\Models\SchedulePlace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Utilities\DataHelper;

class SchedulePlaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SchedulePlace::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fn () => $this->faker->unique()->streetAddress(),
            'abbreviation' => fn () => $this->faker->unique()->streetName(),
            'price_per_hour' => fn () => $this->faker->numberBetween(20, 100) * 10,
            'order_reverse' => fn () => 0,
        ];
    }
}
