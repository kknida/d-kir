<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Kir extends Model {
    protected $fillable = ['ruangan_id', 'barang_id', 'kondisi', 'foto_kondisi_lokasi'];

    public function barang() { return $this->belongsTo(Barang::class); }
    public function ruangan() { return $this->belongsTo(Ruangan::class); }
    public function historiKondisi() { return $this->hasMany(KondisiHistory::class); }
}