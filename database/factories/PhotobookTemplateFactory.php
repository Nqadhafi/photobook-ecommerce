<?php

namespace Database\Factories;

use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotobookTemplateFactory extends Factory
{
    protected $model = PhotobookTemplate::class;

    public function definition()
    {
        return [
            'product_id' => PhotobookProduct::factory(),
            'name' => $this->faker->words(2, true),
            'layout_data' => [
                'pages' => $this->faker->numberBetween(10, 30),
                'photo_slots' => $this->faker->numberBetween(20, 50),
                'dimensions' => $this->faker->randomElement(['20x20cm', '30x30cm', '15x15cm'])
            ],
            'sample_image' => 'templates/template-' . $this->faker->numberBetween(1, 10) . '.jpg',
            'is_active' => true,
        ];
    }
}