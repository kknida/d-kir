<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'nama' => 'Test User',
        //     'user' => 'test@example.com',
        // ]);

        $this->call([
            UserSeeder::class,
            KategoriSeeder::class,
            BrandSeeder::class,
            TipeSeeder::class, 
            CabangSeeder::class, 
            GedungSeeder::class,
            LantaiSeeder::class,
            RuanganSeeder::class,
            JabatanSeeder::class,
            PenanggungJawabSeeder::class
        ]);
    }
}
