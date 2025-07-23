<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\PhotobookOrder;
use App\Models\PhotobookNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotobookNotificationFactory extends Factory
{
    protected $model = PhotobookNotification::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'order_id' => PhotobookOrder::factory(),
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->sentence(10),
            'is_read' => false,
            'action_url' => $this->faker->optional()->url(),
        ];
    }

    public function read()
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}