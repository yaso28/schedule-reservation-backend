<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Utilities\DataHelper;

class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fn () => $this->faker->unique()->country(),
            'guard_name' => fn () => 'web',
            'order_reverse' => fn () => 0,
        ];
    }
}
