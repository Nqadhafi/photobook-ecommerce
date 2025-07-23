<?php

namespace Database\Factories;

use App\Models\PhotobookOrder;
use App\Models\PhotobookOrderItem;
use App\Models\PhotobookOrderFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotobookOrderFileFactory extends Factory
{
    protected $model = PhotobookOrderFile::class;

    public function definition()
    {
        return [
            'order_id' => PhotobookOrder::factory(),
            'order_item_id' => PhotobookOrderItem::factory(),
            'file_path' => 'uploads/files/' . $this->faker->uuid() . '.jpg',
            'status' => 'uploaded',
            'uploaded_at' => now(),
        ];
    }

    public function confirmed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function rejected()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }
}