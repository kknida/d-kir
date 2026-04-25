<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\Ruangan;
use Livewire\Volt\Volt; // Pastikan ini di-import di atas
use App\Models\Barang;        // TAMBAHKAN INI
use App\Models\Kir;           // TAMBAHKAN INI
use App\Models\MutasiBarang;  // TAMBAHKAN INI

Route::get('/', function () {
    return view('welcome');
});

// Route Dashboard Default
// GANTI ROUTE DASHBOARD ANDA DENGAN INI (Lebih Ringkas)
Volt::route('/dashboard', 'dashboard')->name('dashboard');

// Route Profile Default (Bawaan Laravel Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Grup Route untuk User yang Sudah Login dan Terverifikasi
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Grup Khusus Admin (Berdasarkan Gate 'access-admin')
    Route::middleware(['can:access-admin'])->group(function () {
        
        // 1. Route Import Data SAP KCL (Typo diperbaiki)
        Route::get('/import-kcl', function () {
            // Pastikan Anda memiliki file resources/views/import-kcl.blade.php
            return view('import-kcl'); 
        })->name('sap.kcl.import');

        // 2. Route Import Data SAP ASSET (Penambahan Baru)
        Route::get('/import-asset', function () {
            // Pastikan Anda memiliki file resources/views/import-asset.blade.php
            return view('import-asset'); 
        })->name('sap.asset.import');

        Route::get('/katalog-barang', function () {
            return view('katalog.index'); // Mengarah ke file index di folder katalog
        })->name('katalog.index');

        Route::get('/manajemen-barang', function () {
            return view('barang.index');
        })->middleware(['auth', 'can:access-admin'])->name('barang.index');
        
        Route::get('/manajemen-lokasi', function () {
            return view('lokasi.index');
        })->name('lokasi.index');

        Route::get('/manajemen-sdm', function () {
            return view('sdm.index');
        })->name('sdm.index');

        Route::get('/scan-ruangan/{kode}', function ($kode) {
            // Ambil data Ruangan berdasarkan QR yang discan
            $ruangan = \App\Models\Ruangan::with(['penanggungJawab'])->where('kode_ruangan', $kode)->firstOrFail();
            
            return view('lokasi.audit-ruangan', compact('ruangan'));
        })->name('scan.ruangan');

        // TAMBAHKAN RUTE INI UNTUK LAPORAN MUTASI
        Route::get('/laporan-mutasi', function () {
            return view('laporan.mutasi'); 
        })->name('laporan.mutasi');
        
        Route::get('/data-kir', function () {
            return view('inventaris.index');
        })->name('data.kir');

        Route::get('/ruangan/{ruangan}/aset', function (Ruangan $ruangan) {
            // Memuat view blade yang akan menampung komponen Livewire
            return view('lokasi.kelola-aset', compact('ruangan'));
        })->name('ruangan.aset');

    });
    
});

// Tambahkan ini di LUAR middleware auth
Route::get('/scan/{kode}', function ($kode) {
    $barang = App\Models\Barang::with(['sourceable', 'tipe', 'brand'])
                ->where('kode_inventaris', $kode)
                ->firstOrFail();
                
    return view('barang.scan', compact('barang'));
})->name('scan.barang');

require __DIR__.'/auth.php';