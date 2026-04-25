<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Admin Utama
        User::updateOrInsert(
            ['user' => 'admin'], // Gunakan kolom 'user' sebagai unique identifier
            [
                'nama' => 'Administrator', // Sesuaikan jika kolom ini 'nama' di database Anda
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
                'role' => 'admin_pusat',
            ]
        );

        User::updateOrInsert(
            ['user' => 'Staf'],
            [
                'nama' => 'Staff',
                'password' => Hash::make('staf123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}