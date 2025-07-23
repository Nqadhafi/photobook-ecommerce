<?php

namespace Database\Seeders;

use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use Illuminate\Database\Seeder;

class PhotobookTemplatesTableSeeder extends Seeder
{
    public function run()
    {
        $products = PhotobookProduct::all();

        foreach ($products as $product) {
            // Template untuk setiap produk
            PhotobookTemplate::create([
                'product_id' => $product->id,
                'name' => $product->name . ' - Template Standard',
                'layout_data' => [
                    'pages' => 20,
                    'photo_slots' => 30,
                    'dimensions' => '20x20cm',
                    'layout_type' => 'standard'
                ],
                'sample_image' => 'templates/standard-' . $product->id . '.jpg',
                'is_active' => true,
            ]);

            PhotobookTemplate::create([
                'product_id' => $product->id,
                'name' => $product->name . ' - Template Wedding',
                'layout_data' => [
                    'pages' => 24,
                    'photo_slots' => 40,
                    'dimensions' => '20x20cm',
                    'layout_type' => 'wedding'
                ],
                'sample_image' => 'templates/wedding-' . $product->id . '.jpg',
                'is_active' => true,
            ]);

            PhotobookTemplate::create([
                'product_id' => $product->id,
                'name' => $product->name . ' - Template Travel',
                'layout_data' => [
                    'pages' => 16,
                    'photo_slots' => 25,
                    'dimensions' => '20x20cm',
                    'layout_type' => 'travel'
                ],
                'sample_image' => 'templates/travel-' . $product->id . '.jpg',
                'is_active' => true,
            ]);
        }
    }
}