<?php

namespace Database\Factories;

use App\Models\CategoryPermission;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;
use App\Models\Permission;

class CategoryPermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CategoryPermission::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'category_name' => fn () => Category::factory()->create()->name,
            'permission_name' => fn () => Permission::factory()->create()->name,
            'read_only' => fn () => $this->faker->boolean(),
        ];
    }
}
