<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fn () => $this->faker->firstName,
            'email' => fn () => $this->faker->unique()->safeEmail,
            'email_verified_at' => fn () => now(),
            'password' => fn () => Hash::make('password'),
            'remember_token' => fn () => Str::random(10),
        ];
    }
}
