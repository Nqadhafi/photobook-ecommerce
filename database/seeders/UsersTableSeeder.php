<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // User admin untuk testing
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // User customer untuk testing
        User::create([
            'name' => 'Customer User',
            'email' => 'customer@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Buat beberapa user tambahan
        User::factory()->count(10)->create();
    }
}