<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $fillable = ['nama', 'keterangan', 'koordinat'];

    public function gedungs()
    {
        return $this->hasMany(Gedung::class);
    }
}