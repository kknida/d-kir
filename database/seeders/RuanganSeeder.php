<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['lantai_id' => 1, 'nama' => 'Administrasi Teknik'],
            ['lantai_id' => 1, 'nama' => 'Junior Manager CNSO dan Junior Manager Fasilitas Penunjang'],
            ['lantai_id' => 1, 'nama' => 'Manager Fasilitas Teknik'],
            ['lantai_id' => 1, 'nama' => 'Manager Teknik'],
            ['lantai_id' => 1, 'nama' => 'Supervisor dan Teknisi Pelaksana'],
            ['lantai_id' => 1, 'nama' => 'Rest Room Teknik'],
            ['lantai_id' => 1, 'nama' => 'K2S'],
            ['lantai_id' => 1, 'nama' => 'Gudang Bawah Tangga'],
            ['lantai_id' => 1, 'nama' => 'Equipment'],
            ['lantai_id' => 1, 'nama' => 'ARO'],
            ['lantai_id' => 1, 'nama' => 'Pantry'],
            ['lantai_id' => 1, 'nama' => 'Toilet Wanita'],
            ['lantai_id' => 1, 'nama' => 'Toilet Pria'],
            ['lantai_id' => 1, 'nama' => 'Storage'],
            ['lantai_id' => 1, 'nama' => 'MCC'],
            ['lantai_id' => 2, 'nama' => 'General Manager Airnav'],
            ['lantai_id' => 2, 'nama' => 'Sekretaris General Manager Airnav'],
            ['lantai_id' => 2, 'nama' => 'Briefing'],
            ['lantai_id' => 2, 'nama' => 'Manager Perencanaan & Evaluasi Operasi'],
            ['lantai_id' => 2, 'nama' => 'Tamu'],
            ['lantai_id' => 2, 'nama' => 'Junior Manager dan Staff ADM & Keuangan', 'penanggung_jawab_id' => '3'],
            ['lantai_id' => 2, 'nama' => 'Manager Operasi'],
            ['lantai_id' => 2, 'nama' => 'Ruang APP'],
            ['lantai_id' => 2, 'nama' => 'Ruang Rapat', 'penanggung_jawab_id' => '3'],
            ['lantai_id' => 2, 'nama' => 'Manager Administrasi & Keuangan', 'penanggung_jawab_id' => '3'],
            ['lantai_id' => 2, 'nama' => 'Rest Room Operasi'],
            ['lantai_id' => 2, 'nama' => 'Computer Based Training (CBT)'],
            ['lantai_id' => 2, 'nama' => 'Mushola', 'penanggung_jawab_id' => '3'],
            ['lantai_id' => 2, 'nama' => 'Pantry', 'penanggung_jawab_id' => '3'],
            ['lantai_id' => 2, 'nama' => 'Toilet Wanita'],
            ['lantai_id' => 2, 'nama' => 'Toilet Pria'],
            ['lantai_id' => 2, 'nama' => 'Storage'],
            ['lantai_id' => 2, 'nama' => 'Ruang Penyimpanan (ex. BMKG)', 'penanggung_jawab_id' => '3'],
            ['lantai_id' => 3, 'nama' => 'Pelayanan Informasi Aeronautika (PIA)'],
            ['lantai_id' => 3, 'nama' => 'ATIS'],
            ['lantai_id' => 3, 'nama' => 'Peralatan AMSC'],
            ['lantai_id' => 3, 'nama' => 'MCC'],
        ];

        foreach ($data as $index => $item) {
            // Generate kode_ruangan otomatis (Contoh: RGN-L1-01)
            $kode = 'RGN-L' . $item['lantai_id'] . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);

            DB::table('ruangans')->insert([
                'lantai_id'           => $item['lantai_id'],
                'nama'                => $item['nama'],
                'kode_ruangan'        => $kode,
                'qrcode_path'         => null, // Bisa diisi path default jika ada
                'penanggung_jawab_id' => $item['penanggung_jawab_id'] ?? null,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }
}