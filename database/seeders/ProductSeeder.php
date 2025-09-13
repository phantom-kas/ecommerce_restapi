<?php

namespace Database\Seeders;

use App\Models\Products;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        // Example seed data (replace IDs with real ones from your brand & category tables)
        $faker = fake();

        // Assuming you already have some brand & category IDs
        $brandIds    = DB::table('brand')->pluck('id')->toArray();
        $categoryIds = DB::table('category')->pluck('id')->toArray();

        // Create 10 random products
        for ($i = 0; $i < 10; $i++) {
            $product = Products::create([
                'name'        => $faker->unique()->words(3, true), // e.g. "Super Smart Watch"
                'description' => $faker->sentence(12),
                'price'       => $faker->numberBetween(5000, 500000), // stored in cents
                'quantity'    => $faker->numberBetween(0, 100),
                'media'       => json_encode([]), // no media in seeder
                'created_by'  => 1, // adjust if you have multiple users
            ]);

            // Pick random brand & category
            $brandId    = $faker->randomElement($brandIds);
            $categoryId = $faker->randomElement($categoryIds);

            DB::table('products_brand')->insert([
                'product_id' => $product->id,
                'brand_id'   => $brandId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('products_category')->insert([
                'product_id'  => $product->id,
                'category_id' => $categoryId,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);
        }
    }
}
