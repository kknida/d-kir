<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            // Keamanan & Safety
            'Hikvision', 'Dahua', 'Garrett', 'Yamato', 'Servvo', 'ZKTeco', 'Honeywell', 'Simplex', 'ChubbSafes', 'Nikon', 'Maglite', 'Motorola', 'Polygon', 'Delta',
            
            // AC & Kelistrikan
            'Panasonic', 'Daikin', 'Mitsubishi', 'Gree', 'KDK', 'Maspion', 'Schneider', 'Perkins', 'Matsunaga', 'APC', 'Eaton', 'Uticon', 'Krisbow', 'ABB', 'Osram', 'Nokian', 'Bardi', 'Theben', 'VRLA',
            
            // Alat Ukur & Maintenance
            'Fluke', 'Kyoritsu', 'Megger', 'HIOKI', 'Tektronix', 'Lutron', 'Extech', 'Sanwa', 'Bosch', 'Omron', 'Wika', 'Hanna', 'Xiaomi', 'Casio', 'Camry', 'Makita', 'Tekiro', 'Stanley', 'Hakko', 'Lakoni', 'Shark', 'Karcher', 'Honda', 'Tajima',
            
            // Pantry & Dapur
            'Modena', 'Samsung', 'LG', 'Nescafe', 'Electrolux', 'Yong Ma', 'Oxone', 'Rinnai', 'Pertamina', 'Sango', 'Kedaung', 'Royal',
            
            // IT & Gadget
            'Dell', 'HP', 'HPE', 'Cisco', 'MikroTik', 'Ubiquiti', 'Seagate', 'Synology', 'Logitech', 'Jabra', 'Lenovo', 'Vention', 'SanDisk', 'Anker', 'Belden', 'Pro\'sKit', 'Noyafa',
            
            // Furniture & Perlengkapan
            'Informa', 'Secretlab', 'Olympic', 'Chitose', 'Brother', 'Lion', 'IKEA', 'Onna', 'Indachi',
            
            // Printer & Alat Kantor
            'Canon', 'Kyocera', 'Fujitsu', 'GBC', 'Joyko', 'Kenko', 'Sakana', 'Solution', 'PaperOne', 'Artline', 'Kangaro', 'Trodat', 'Ideal',
            
            // Elektronik & Multimedia
            'Sony', 'Epson', 'JK Screen', 'Bose', 'Yamaha', 'Shure', 'Manfrotto', 'BrightSign', 'Audio-Technica', 'DJI',
            
            // Sarana Prasarana
            'Penguin', 'Shimizu', 'Gorgy Timing', 'Onemed', 'Custom'
        ];

        // Hapus duplikat jika ada dan urutkan
        $uniqueBrands = array_unique($brands);
        sort($uniqueBrands);

        $data = [];
        foreach ($uniqueBrands as $brand) {
            $data[] = [
                'nama' => $brand,
                'keterangan' => 'Brand untuk inventaris kantor',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        DB::table('brands')->insert($data);
    }
}