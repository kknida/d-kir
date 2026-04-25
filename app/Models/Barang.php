<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne; // Tambahkan ini
use Illuminate\Database\Eloquent\Relations\MorphTo; // Tambahkan ini

class Barang extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'kode_inventaris', 
        'sourceable_id', 
        'sourceable_type', 
        'tipe_id', 
        'brand_id', 
        'keterangan', 
        'foto_barang',
        'is_active',
        'ruangan_id',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->kode_inventaris)) {
                $model->kode_inventaris = 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5));
            }
        });
    }

    /**
     * PENTING: Relasi untuk Modal Detail (Daftar Histori)
     * Tanpa ini, Modal Detail akan Error saat menampilkan tabel riwayat.
     */
    public function mutasiBarangs(): HasMany
    {
        return $this->hasMany(MutasiBarang::class, 'barang_id');
    }

    /**
     * Relasi untuk Badge "Kondisi Terakhir" di tabel utama
     * Menggunakan latestOfMany agar query sangat cepat (hanya ambil 1 data terbaru).
     */
    public function mutasiTerakhir(): HasOne
    {
        return $this->hasOne(MutasiBarang::class, 'barang_id')->latestOfMany('tanggal_mutasi');
    }

    /**
     * Relasi Polymorphic ke SAP Asset atau SAP KCL
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tipe()
    {
        return $this->belongsTo(Tipe::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function kirs(): HasMany
    {
        return $this->hasMany(Kir::class);
    }

    public function lokasiTerkini(): HasOne
    {
        return $this->hasOne(Kir::class)->latestOfMany();
    }
}