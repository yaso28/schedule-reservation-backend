<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;

class SettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'category_name' => fn () => Category::factory()->create()->name,
            'key_name' => fn () => $this->faker->word,
            'value' => fn () => $this->faker->word,
            'description' => fn () => $this->faker->realText(30),
        ];
    }
}
