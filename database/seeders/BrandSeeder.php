<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
        $brands = [
            [
                'name' => 'Apple',
                'description' => 'Electronics, computers, and smartphones',
            ],
            [
                'name' => 'Samsung',
                'description' => 'Consumer electronics and appliances',
            ],
            [
                'name' => 'Nike',
                'description' => 'Sportswear and footwear',
            ],
            [
                'name' => 'Adidas',
                'description' => 'Athletic apparel and footwear',
            ],
            [
                'name' => 'Sony',
                'description' => 'Electronics and entertainment',
            ],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
