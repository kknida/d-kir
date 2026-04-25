<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TipeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Keamanan, Hobi & Lain-lain' => [
                'CCTV Dome', 'CCTV PTZ Speed Dome', 'Metal Detector Handheld', 'APAR', 
                'Access Control Card Reader', 'Smoke Detector Wireless', 'Fire Alarm Control Panel', 
                'Brankas Besi Tahan Api', 'Teleskop Binocular', 'Emergency Lamp LED', 
                'Handy Talky (HT)', 'Sepeda'
            ],
            'AC & Kelistrikan' => [
                'AC Split', 'AC Cassette', 'AC Floor Standing', 'Exhaust Fan Dinding', 
                'Kipas Angin Berdiri', 'Panel Listrik LVMDP', 'Stabilizer', 'UPS', 
                'Kabel Roll', 'Tangga', 'MCB', 'Lampu LED', 'Lampu High Bay Industrial', 
                'Capacitor Bank', 'Smart Plug WiFi', 'Baterai Kering Deep Cycle'
            ],
            'Alat Ukur & Instrumentasi' => [
                'Multimeter Digital', 'Clamp Meter (Tang Ampere)', 'Insulation Tester (Megger)', 
                'Earth Tester', 'Oscilloscope Portable', 'Thermometer Infrared'
            ],
            'Peralatan Dapur / Pantry' => [
                'Dispenser Galon Bawah', 'Microwave 20L', 'Kulkas 2 Pintu', 'Coffee Maker', 
                'Electric Kettle', 'Magic Com', 'Toaster (Pemanggang Roti)', 'Kitchen Sink Stainless', 'Kitchen Set'
            ],
            'IT & Gadget' => [
                'Komputer Desktop Core i3', 'Komputer Desktop Core i5', 'Komputer Desktop Core i7', 
                'Komputer All In One Core i3', 'Komputer All In One Core i5', 'Komputer All In One Core i7', 
                'Laptop Core i3', 'Laptop Core i5', 'Laptop Core i7', 'Mini PC Core i3', 'Mini PC Core i5', 
                'Monitor 24 inch IPS', 'Server Rackmount 2U', 'Switch Managed 24 Port POE', 
                'Router Core', 'Access Point WiFi 6', 'External Drive', 'NAS Storage', 'Tablet', 
                'Keyboard & Mouse Wireless', 'Webcam Teleconfrence', 'Headset Noise Cancelling', 
                'Docking Station Laptop', 'Kabel HDMI', 'Kabel LAN Cat6', 'Crimping Tool RJ45', 
                'LAN Tester Digital'
            ],
            'Furniture & Perlengkapan Ruangan' => [
                'Kursi Kerja Sandaran Tinggi', 'Kursi Kerja Sandaran Rendah', 'Meja Kerja Staff L-Shape', 
                'Kursi Kerja Ergonomis', 'Meja Rapat Oval', 'Kursi Rapat Hidrolik', 'Lemari Arsip Besi 2 Pintu', 
                'Filling Cabinet', 'Sofa Tamu Minimalis', 'Meja Tamu Kaca', 'Rak Buku Perpustakaan', 
                'Loker Karyawan', 'Cermin Dinding Besar', 'Karpet Kantor Bulu', 'Gorden / Blind Vertical', 
                'Meja Resepsionis', 'Kursi Tunggu Bandara (4 Dudukan)', 'Lemari Display Kaca (Trofi)', 
                'Meja Pantry Tinggi', 'Kursi Bar Pantry'
            ],
            'Printer & Peralatan Kantor' => [
                'Printer Laserjet', 'Printer', 'Printer All In One', 'Printer Dot Matrix', 
                'Mesin Fotocopy Multifungsi', 'Scanner Dokumen High Speed', 
                'Paper Shredder (Penghancur Kertas)', 'Pemotong Kertas Besar (Guillotine)', 
                'Pelubang Kertas Besar', 'Papan Tulis Whiteboard', 'Flipchart Stand', 
                'Label Printer (Barcode)', 'Mesin Absensi Sidik Jari/Wajah'
            ],
            'Alat Teknik & Maintenance' => [
                'Bor Listrik Impact', 'Gerinda Tangan', 'Set Kunci Pas (8-32mm)', 'Obeng Set Presisi', 
                'Solder Station Digital', 'Tang Kombinasi', 'Gergaji Kayu', 'Palu Kambing', 
                'Toolbox Besi 3 Susun', 'Kunci Inggris 12 inch', 'Mesin Las Inverter', 
                'Kompresor Angin 1 HP', 'Vacuum Cleaner Industrial', 'Jet Washer (Steam Air)', 
                'Mesin Potong Rumput Gendong', 'Tangga Aluminium Lipat 5m', 'Meteran Gulung', 
                'Tespen Listrik'
            ],
            'Elektronik & Multimedia' => [
                'Smart TV', 'Proyektor', 'Layar Proyektor Motorized', 'Speaker Aktif PA System', 
                'Mixer Audio 12 Channel', 'Microphone Wireless Set', 'Kamera DSLR Video', 
                'Kamera Mirrorless', 'Tripod Kamera Professional', 'Pointer Laser Presentation', 
                'Voice Recorder Digital', 'Bracket TV Dinding', 'Stabilizer Kamera (Gimbal)'
            ],
            'Sarana & Prasarana Kantor' => [
                'Tempat Parkir Sepeda', 'Tangki Air (Tandon)', 'Pompa Air Jetpump', 
                'Jam Dinding Digital (GPS Sync)', 'Peta Dinding Wilayah Navigasi', 
                'Kotak P3K Lengkap', 'Papan Informasi Akrilik', 'Asbak Berdiri Stainless'
            ],
        ];

        foreach ($data as $kategoriNama => $tipes) {
            $kategori = DB::table('kategoris')->where('nama', $kategoriNama)->first();

            if ($kategori) {
                foreach ($tipes as $tipeNama) {
                    DB::table('tipes')->updateOrInsert(
                        ['kategori_id' => $kategori->id, 'nama' => $tipeNama],
                        [
                            'keterangan' => 'Tipe master untuk ' . $tipeNama,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]
                    );
                }
            }
        }
    }
}