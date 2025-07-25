<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
class PhotobookProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
                $products = [
            [
                'name' => 'Classic Photobook 20x20cm',
                'description' => 'Buku foto klasik dengan ukuran 20x20cm, 20 halaman',
                'price' => 150000,
                'thumbnail' => '/storage/products/classic-20x20.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Premium Photobook 30x30cm',
                'description' => 'Buku foto premium dengan ukuran 30x30cm, 30 halaman',
                'price' => 250000,
                'thumbnail' => '/storage/products/premium-30x30.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Mini Photobook 15x15cm',
                'description' => 'Buku foto mini dengan ukuran 15x15cm, 10 halaman',
                'price' => 100000,
                'thumbnail' => '/storage/products/mini-15x15.jpg',
                'is_active' => true,
            ],
        ];

        foreach ($products as $productData) {
            $product = PhotobookProduct::create($productData);
            
            // Create sample templates for each product
            $templates = [
                [
                    'product_id' => $product->id,
                    'name' => 'Wedding Template',
                    'sample_image' => '/storage/templates/wedding-sample.jpg',
                    'layout_data' => json_encode([
                        'pages' => 20,
                        'photo_slots' => 30,
                        'theme' => 'wedding'
                    ]),
                    'is_active' => true,
                ],
                [
                    'product_id' => $product->id,
                    'name' => 'Family Template',
                    'sample_image' => '/storage/templates/family-sample.jpg',
                    'layout_data' => json_encode([
                        'pages' => 20,
                        'photo_slots' => 25,
                        'theme' => 'family'
                    ]),
                    'is_active' => true,
                ],
                [
                    'product_id' => $product->id,
                    'name' => 'Travel Template',
                    'sample_image' => '/storage/templates/travel-sample.jpg',
                    'layout_data' => json_encode([
                        'pages' => 20,
                        'photo_slots' => 35,
                        'theme' => 'travel'
                    ]),
                    'is_active' => true,
                ],
            ];
            
            foreach ($templates as $templateData) {
                PhotobookTemplate::create($templateData);
            }
        }
    }
}
