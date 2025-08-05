<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
                // Definisikan data untuk user super admin
        // Anda bisa mengganti nilai default ini atau membuatnya dapat dikonfigurasi
        $superAdminData = [
            'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
            'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@example.com'),
            'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password123')), // Gunakan password yang kuat!
            'role' => 'super_admin', // Pastikan nilai ini sesuai dengan yang diharapkan oleh model/kolom
            // 'email_verified_at' => now(), // Opsional: langsung verifikasi email
            // 'remember_token' => Str::random(10), // Opsional
        ];

        // Cek apakah user dengan email ini sudah ada
        $existingUser = User::where('email', $superAdminData['email'])->first();

        if ($existingUser) {
            // Jika sudah ada, perbarui role-nya menjadi super_admin sebagai pengamanan
            if ($existingUser->role !== 'super_admin') {
                $existingUser->update(['role' => 'super_admin']);
                $this->command->info("Existing user with email {$superAdminData['email']} found. Role updated to 'super_admin'.");
            } else {
                 $this->command->info("User with email {$superAdminData['email']} already exists as 'super_admin'.");
            }
        } else {
            // Jika belum ada, buat user baru
            User::create($superAdminData);
            $this->command->info("Super Admin user created successfully with email: {$superAdminData['email']}");
        }
    }
}
