<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LantaiSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['gedung' => 'Administration & Operational (AOB)', 'nama' => 'Lantai 1'],
            ['gedung' => 'Administration & Operational (AOB)', 'nama' => 'Lantai 2'],
            ['gedung' => 'Administration & Operational (AOB)', 'nama' => 'Lantai 3'],
            ['gedung' => 'Administration & Operational (AOB)', 'nama' => 'Lantai 14'],
        ];

        foreach ($data as $item) {
            $gedungId = DB::table('gedungs')->where('nama', $item['gedung'])->value('id');

            if ($gedungId) {
                DB::table('lantais')->updateOrInsert(
                    ['nama' => $item['nama'], 'gedung_id' => $gedungId],
                    [
                        'keterangan' => null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );
            }
        }
    }
}