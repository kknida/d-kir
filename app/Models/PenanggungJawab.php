<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenanggungJawab extends Model
{
    use HasFactory;

    protected $fillable = [
        'jabatan_id',
        'nama',
        'nip',
        'kontak',
        'keterangan'
    ];

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class);
    }
}