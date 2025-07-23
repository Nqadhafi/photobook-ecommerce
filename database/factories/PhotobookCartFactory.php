<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use App\Models\PhotobookCart;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotobookCartFactory extends Factory
{
    protected $model = PhotobookCart::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'product_id' => PhotobookProduct::factory(),
            'template_id' => PhotobookTemplate::factory(),
            'quantity' => $this->faker->numberBetween(1, 3),
            'design_same' => $this->faker->boolean(),
            'session_id' => $this->faker->uuid(),
        ];
    }
}