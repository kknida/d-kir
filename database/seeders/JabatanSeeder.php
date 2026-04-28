<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        $jabatans = [
            'Manager Administrasi dan Keuangan',
            'Manager Fasilitas Teknik',
            'Manager Teknik 1',
            'Manager Teknik 2',
            'Manager Teknik 3',
            'Manager Teknik 4',
            'Manager Operasi 1',
            'Manager Operasi 2',
            'Manager Operasi 3',
            'Manager Operasi 4',
            'Manager Operasi 5',
            'Junior Manager Personalia dan Umum',
            'Junior Manager Keuangan',
            'Junior Manager CNS dan Otomasi',
            'Junior Manager Fasilitas Penunjang',
        ];

        foreach ($jabatans as $nama) {
            DB::table('jabatans')->insert([
                'nama' => $nama,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}