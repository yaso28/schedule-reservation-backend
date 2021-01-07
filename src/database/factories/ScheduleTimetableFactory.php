<?php

namespace Database\Factories;

use App\Models\ScheduleTimetable;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleTimetableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduleTimetable::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fn () => $this->faker->unique()->word,
            'details' => fn () => $this->faker->realText(100),
            'order_reverse' => fn () => 0,
        ];
    }
}
