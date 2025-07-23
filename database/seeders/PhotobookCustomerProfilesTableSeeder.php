<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PhotobookCustomerProfile;
use Illuminate\Database\Seeder;

class PhotobookCustomerProfilesTableSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        foreach ($users as $user) {
            PhotobookCustomerProfile::create([
                'user_id' => $user->id,
                'phone_number' => '08123456789' . rand(0, 9),
                'address' => 'Jalan Merdeka No. ' . rand(1, 100),
                'city' => 'Jakarta',
                'postal_code' => '12345',
                'province' => 'DKI Jakarta',
                'country' => 'ID',
            ]);
        }
    }
}