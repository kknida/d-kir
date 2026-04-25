<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gedung extends Model
{
    use HasFactory;

    // 1. Tambahkan ini agar aman dari Mass Assignment Error
    protected $fillable = [
        'cabang_id', 
        'nama', 
        'alamat', 
        'koordinat'
    ];

    // 2. Relasi ke atas (Gedung ini milik Cabang apa?)
    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    // 3. Relasi ke bawah (Gedung ini punya Lantai apa saja?)
    public function lantais()
    {
        return $this->hasMany(Lantai::class);
    }
}