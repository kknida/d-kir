<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GedungSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'cabang' => 'Kantor Cabang Surabaya', 
                'nama' => 'Administration & Operational (AOB)', 
                'alamat' => 'Segoro Tambak, Sedati, Segoro Tambak, Segorotambak, Sidoarjo, Kabupaten Sidoarjo, Jawa Timur 61253', 
                'koordinat' => '-7.374163, 112.788095' // Sudah diperbaiki dari 'koordinar' menjadi 'koordinat'
            ],
            ['cabang' => 'Kantor Cabang Surabaya', 'nama' => 'Radar Head'],
            ['cabang' => 'Kantor Cabang Pembantu Banyuwangi', 'nama' => 'Gedung Tower'],
        ];

        foreach ($data as $item) {
            $cabangId = DB::table('cabangs')->where('nama', $item['cabang'])->value('id');

            if ($cabangId) {
                DB::table('gedungs')->updateOrInsert(
                    ['nama' => $item['nama'], 'cabang_id' => $cabangId], // Pencarian data berdasarkan nama dan cabang
                    [
                        // Tambahkan baris di bawah ini agar data tersimpan:
                        'alamat' => $item['alamat'] ?? null,
                        'koordinat' => $item['koordinat'] ?? null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );
            }
        }
    }
}