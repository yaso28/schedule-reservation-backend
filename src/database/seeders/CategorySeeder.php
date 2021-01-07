<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $order = 1;
        foreach (array_reverse([
            [Category::RESERVATION],
            [Category::RESERVATION_PUBLIC],
        ]) as $data) {
            Category::updateOrCreate(
                [
                    'name' => $data[0]
                ],
                [
                    'order_reverse' => $order++,
                ]
            );
        }
    }
}
