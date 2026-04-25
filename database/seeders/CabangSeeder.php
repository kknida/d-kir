<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CabangSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Kantor Cabang Surabaya', 'keterangan' => null, 'koordinat' => null],
            ['nama' => 'Kantor Cabang Pembantu Banyuwangi', 'keterangan' => null, 'koordinat' => null],
            ['nama' => 'Kantor Cabang Pembantu Malang', 'keterangan' => null, 'koordinat' => null],
            ['nama' => 'Kantor Cabang Pembantu Sumenep', 'keterangan' => null, 'koordinat' => null],
            ['nama' => 'Unit Bawean', 'keterangan' => null, 'koordinat' => null],
            ['nama' => 'Unit Blora', 'keterangan' => null, 'koordinat' => null],
            ['nama' => 'Unit Jember', 'keterangan' => null, 'koordinat' => null],
            ['nama' => 'Unit Kediri', 'keterangan' => null, 'koordinat' => null],
        ];

        foreach ($data as $item) {
            DB::table('cabangs')->updateOrInsert(
                ['nama' => $item['nama']],
                array_merge($item, [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])
            );
        }
    }
}