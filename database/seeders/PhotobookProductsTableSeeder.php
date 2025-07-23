<?php

namespace Database\Seeders;

use App\Models\PhotobookProduct;
use Illuminate\Database\Seeder;

class PhotobookProductsTableSeeder extends Seeder
{
    public function run()
    {
        PhotobookProduct::create([
            'name' => 'Photobook Classic 20x20cm',
            'description' => 'Photobook ukuran 20x20cm dengan kualitas tinggi, cocok untuk kenangan spesial Anda.',
            'price' => 199000,
            'thumbnail' => 'products/classic-20x20.jpg',
            'is_active' => true,
        ]);

        PhotobookProduct::create([
            'name' => 'Photobook Premium 30x30cm',
            'description' => 'Photobook ukuran 30x30cm dengan kertas foto berkualitas tinggi dan hard cover.',
            'price' => 349000,
            'thumbnail' => 'products/premium-30x30.jpg',
            'is_active' => true,
        ]);

        PhotobookProduct::create([
            'name' => 'Photobook Mini 15x15cm',
            'description' => 'Photobook ukuran mini 15x15cm, cocok untuk koleksi foto harian.',
            'price' => 129000,
            'thumbnail' => 'products/mini-15x15.jpg',
            'is_active' => true,
        ]);

        // Buat beberapa produk tambahan menggunakan factory
        PhotobookProduct::factory()->count(5)->create();
    }
}