<?php

namespace Database\Factories;

use App\Models\PhotobookOrder;
use App\Models\PhotobookProduct;
use App\Models\PhotobookTemplate;
use App\Models\PhotobookOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotobookOrderItemFactory extends Factory
{
    protected $model = PhotobookOrderItem::class;

    public function definition()
    {
        return [
            'order_id' => PhotobookOrder::factory(),
            'product_id' => PhotobookProduct::factory(),
            'template_id' => PhotobookTemplate::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 50000, 500000),
            'design_same' => $this->faker->boolean(),
        ];
    }
}