<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PenanggungJawabSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'jabatan_id' => 1,
                'nama'       => 'Dolby Rudolf Gumilar',
                'nip'        => '10010113',
            ],
            [
                'jabatan_id' => 2,
                'nama'       => 'An Naufal',
                'nip'        => '10010069',
            ],
            [
                'jabatan_id' => 12,
                'nama'       => 'Nurul Huda',
                'nip'        => 'ASN83751',
            ],
        ];

        foreach ($data as $item) {
            DB::table('penanggung_jawabs')->updateOrInsert(
                ['nip' => $item['nip']], // Cek berdasarkan NIP agar tidak duplikat
                [
                    'jabatan_id' => $item['jabatan_id'],
                    'nama'       => $item['nama'],
                    'kontak'     => null, // Tidak ada di excel, diisi null
                    'keterangan' => null, // Tidak ada di excel, diisi null
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}