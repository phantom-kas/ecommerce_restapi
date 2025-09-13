<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Phones',
                'description' => 'Electronics, computers, and smartphones',
            ],
            [
                'name' => 'Pcs',
                'description' => 'Consumer electronics and appliances',
            ],
            [
                'name' => 'Laptops',
                'description' => 'Sportswear and footwear',
            ],
            [
                'name' => 'Clothes',
                'description' => 'Athletic apparel and footwear',
            ],
            [
                'name' => 'Funiture',
                'description' => 'Electronics and entertainment',
            ],
        ];

        foreach ($brands as $brand) {
            Category::create($brand);
        }
    }
}
