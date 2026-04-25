<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Keamanan, Hobi & Lain-lain',
            'AC & Kelistrikan',
            'Alat Ukur & Instrumentasi',
            'Peralatan Dapur / Pantry',
            'IT & Gadget',
            'Furniture & Perlengkapan Ruangan',
            'Printer & Peralatan Kantor',
            'Alat Teknik & Maintenance',
            'Elektronik & Multimedia',
            'Sarana & Prasarana Kantor'
        ];

        foreach ($categories as $cat) {
            DB::table('kategoris')->updateOrInsert(
                ['nama' => $cat],
                [
                    'keterangan' => 'Kategori master untuk ' . $cat,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}