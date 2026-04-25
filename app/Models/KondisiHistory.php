<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class KondisiHistory extends Model {
    protected $fillable = ['kir_id', 'kondisi_lama', 'kondisi_baru', 'foto_bukti', 'keterangan'];
}