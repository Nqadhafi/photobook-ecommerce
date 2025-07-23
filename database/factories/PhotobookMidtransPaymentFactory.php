<?php

namespace Database\Factories;

use App\Models\PhotobookOrder;
use App\Models\PhotobookMidtransPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotobookMidtransPaymentFactory extends Factory
{
    protected $model = PhotobookMidtransPayment::class;

    public function definition()
    {
        return [
            'order_id' => PhotobookOrder::factory(),
            'snap_token' => $this->faker->uuid(),
            'redirect_url' => $this->faker->url(),
            'transaction_id' => $this->faker->uuid(),
            'fraud_status' => 'accept',
            'transaction_status' => 'settlement',
            'payment_type' => $this->faker->randomElement(['bank_transfer', 'gopay', 'credit_card']),
            'gross_amount' => $this->faker->randomFloat(2, 100000, 1000000),
            'bank' => $this->faker->randomElement(['bca', 'bni', 'mandiri']),
            'va_numbers' => null,
            'bill_key' => null,
            'biller_code' => null,
            'payment_code' => null,
            'status_code' => '200',
            'status_message' => 'Success',
            'merchant_id' => 'M123456789',
            'signature_key' => $this->faker->sha256(),
            'payload' => [],
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_address' => $this->faker->address(),
            'customer_city' => $this->faker->city(),
            'paid_at' => now(),
        ];
    }
}