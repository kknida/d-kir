<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    // Tambahkan ini agar aman dari Mass Assignment
    protected $fillable = ['nama', 'keterangan'];
}