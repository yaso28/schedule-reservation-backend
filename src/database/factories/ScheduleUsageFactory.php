<?php

namespace Database\Factories;

use App\Models\ScheduleUsage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Utilities\DataHelper;
use App\Models\ReservationOrganization;

class ScheduleUsageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduleUsage::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fn () => $this->faker->unique()->country(),
            'is_public' => fn () => true,
            'reservation_organization_id' => fn () => ReservationOrganization::factory(),
            'order_reverse' => fn () => 0,
        ];
    }
}
