<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lantai extends Model
{
    use HasFactory;

    // 1. Kolom yang diizinkan untuk diisi
    protected $fillable = [
        'gedung_id', 
        'nama', 
        'keterangan'
    ];

    // 2. Relasi ke atas (Lantai ini ada di Gedung apa?)
    public function gedung()
    {
        return $this->belongsTo(Gedung::class);
    }

    // 3. Relasi ke bawah (Lantai ini punya Ruangan apa saja?)
    public function ruangans()
    {
        return $this->hasMany(Ruangan::class);
    }
}