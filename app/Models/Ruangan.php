<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Ruangan extends Model
{
    use HasFactory;

    // Pastikan fillable Anda sesuai dengan tabel di database
    protected $fillable = [
        'kode_ruangan',
        'nama',
        'lantai_id',
        'penanggung_jawab_id',
        // kolom lainnya...
    ];

    // 1. Relasi ke Lantai (Gedung)
    public function lantai()
    {
        return $this->belongsTo(Lantai::class);
    }

    // 2. Relasi ke PIC / Penanggung Jawab
    public function penanggungJawab()
    {
        return $this->belongsTo(PenanggungJawab::class, 'penanggung_jawab_id');
    }

    // 3. TAMBAHKAN INI: Relasi ke tabel KIR (Banyak barang di satu ruangan)
    public function kirs()
    {
        return $this->hasMany(Kir::class);
    }

}