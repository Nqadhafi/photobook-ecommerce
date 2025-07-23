<?php

namespace Database\Factories;

use App\Models\PhotobookProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotobookProductFactory extends Factory
{
    protected $model = PhotobookProduct::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 50000, 500000),
            'thumbnail' => 'products/product-' . $this->faker->numberBetween(1, 10) . '.jpg',
            'is_active' => true,
        ];
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}