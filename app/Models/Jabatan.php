<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'keterangan'];

    public function penanggungJawabs()
    {
        return $this->hasMany(PenanggungJawab::class);
    }
}