<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiBarang extends Model
{
    use HasFactory;

    // Pastikan field yang bisa diisi massal sudah terdaftar, 
    // atau gunakan $guarded = [] jika Anda mengizinkan semua field diisi.
    protected $guarded = []; 

    /**
     * 1. Relasi ke tabel Barang
     * Membaca foreign key: barang_id
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    /**
     * 2. Relasi ke tabel Ruangan (Sebagai Ruang Asal)
     * Membaca foreign key: ruangan_asal_id
     */
    public function ruanganAsal()
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_asal_id');
    }

    /**
     * 3. Relasi ke tabel Ruangan (Sebagai Ruang Tujuan)
     * Membaca foreign key: ruangan_tujuan_id
     */
    public function ruanganTujuan()
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_tujuan_id');
    }

    /**
     * 4. Relasi ke tabel Users (Opsional: Siapa PIC yang melakukan mutasi)
     * Membaca foreign key: user_id
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}