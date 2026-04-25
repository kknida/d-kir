<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\Ruangan;
use Livewire\Volt\Volt; // Pastikan ini di-import di atas
use App\Models\Barang;        // TAMBAHKAN INI
use App\Models\Kir;           // TAMBAHKAN INI
use App\Models\MutasiBarang;  // TAMBAHKAN INI
use Illuminate\Support\Facades\Auth;

// Route utama untuk Scan Barcode Ruangan
Route::get('/scan/ruangan/{kode_ruangan}', function ($kode_ruangan) {
    $ruangan = Ruangan::where('kode_ruangan', $kode_ruangan)->firstOrFail();

    // Jika sudah Login
    if (Auth::check()) {
        // Arahkan ke halaman manajemen/inventarisasi yang Anda pakai (kir-detail-table)
        // Sesuaikan nama route-nya dengan route yang Anda gunakan untuk menampilkan kir-detail-table
        return redirect()->route('kir.ruangan.detail', $ruangan->id);
    }

    // Jika belum Login (Skenario 1: Public View)
    return redirect()->route('public.kir.view', $ruangan->kode_ruangan);
})->name('scan.ruangan.gate');

// Route untuk Skenario 1 (Public/Guest)
// Pastikan Anda membuat component Volt/Blade baru untuk tampilan ini
Route::get('/public/kir/{kode_ruangan}', \App\Livewire\Inventaris\PublicKirView::class)
    ->name('public.kir.view');

// Route untuk Skenario 2 (Auth - Halaman yang sudah kita kerjakan sebelumnya)
Route::get('/inventaris/ruangan/{ruangan}', \App\Livewire\Inventaris\KirDetailTable::class)
    ->middleware(['auth'])
    ->name('kir.ruangan.detail');


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