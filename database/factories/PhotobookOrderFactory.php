<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\PhotobookOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotobookOrderFactory extends Factory
{
    protected $model = PhotobookOrder::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'order_number' => 'PB' . strtoupper($this->faker->unique()->bothify('######')),
            'total_amount' => $this->faker->randomFloat(2, 100000, 1000000),
            'status' => 'pending',
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_address' => $this->faker->address(),
            'customer_city' => $this->faker->city(),
            'customer_postal_code' => $this->faker->postcode(),
            'pickup_code' => strtoupper($this->faker->bothify('???###')),
        ];
    }

    public function paid()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function fileUpload()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'file_upload',
            'paid_at' => now(),
        ]);
    }

    public function processing()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'paid_at' => now(),
            'file_uploaded_at' => now(),
        ]);
    }

    public function ready()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ready',
            'paid_at' => now(),
            'file_uploaded_at' => now(),
            'processing_at' => now(),
            'ready_at' => now(),
        ]);
    }

    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'paid_at' => now(),
            'file_uploaded_at' => now(),
            'processing_at' => now(),
            'ready_at' => now(),
            'picked_up_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function cancelled()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}