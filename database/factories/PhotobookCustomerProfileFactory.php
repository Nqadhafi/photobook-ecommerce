<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\PhotobookCustomerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotobookCustomerProfileFactory extends Factory
{
    protected $model = PhotobookCustomerProfile::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'phone_number' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'province' => $this->faker->state(),
            'country' => 'ID',
        ];
    }
}