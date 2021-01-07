<?php

namespace Database\Factories;

use App\Models\ReservationOrganization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Utilities\DataHelper;

class ReservationOrganizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReservationOrganization::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fn () => $this->faker->unique()->secondaryAddress(),
            'abbreviation' => fn () => $this->faker->unique()->lastName(),
            'registration_number' => fn () => $this->faker->numerify('####-######'),
            'order_reverse' => fn () => 0,
        ];
    }
}
