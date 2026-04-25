<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\Ruangan;
use Livewire\Volt\Volt; 
use App\Models\Barang;       
use App\Models\Kir;           
use App\Models\MutasiBarang;  
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| 1. GATEKEEPER & PUBLIC ROUTES (Bisa Diakses Tanpa Login)
|--------------------------------------------------------------------------
*/
// resources/routes/web.php

// --- 1. RUTE RUANGAN (Letakkan paling atas untuk rute /scan) ---
Route::get('/scan/ruangan/{kode_ruangan}', function ($kode_ruangan) {
    $ruangan = App\Models\Ruangan::where('kode_ruangan', $kode_ruangan)->first();
    
    if (!$ruangan) abort(404, "Ruangan dengan kode $kode_ruangan tidak terdaftar.");

    if (Auth::check()) {
        return redirect()->route('kir.ruangan.detail', $ruangan->id);
    }
    return redirect()->route('public.kir.view', $ruangan->kode_ruangan);
})->name('scan.ruangan.gate');

// --- 2. RUTE BARANG SATUAN ---
Route::get('/scan/{kode}', function ($kode) {
    // Pastikan variabel $kode yang masuk benar-benar Kode Inventaris
    $barang = App\Models\Barang::with(['sourceable', 'tipe', 'brand'])
                ->where('kode_inventaris', $kode)
                ->firstOrFail(); // Akan 404 jika kode tidak ada di DB
                
    return view('barang.scan', compact('barang'));
})->name('scan.barang');

// --- 3. RUTE VOLT (Pastikan path file benar) ---
// Jika file ada di: resources/views/livewire/inventaris/public-kir-view.blade.php
Volt::route('/public/kir/{kode_ruangan}', 'inventaris.public-kir-view')
    ->name('public.kir.view');

Volt::route('/inventaris/ruangan/{ruangan}', 'inventaris.kir-detail-table')
    ->middleware(['auth'])
    ->name('kir.ruangan.detail');
    
Route::get('/', function () {
    return view('welcome');
});


/*
|--------------------------------------------------------------------------
| 2. AUTH ROUTES (Harus Login)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard & Profile
    Volt::route('/dashboard', 'dashboard')->name('dashboard');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Manajemen Inventarisasi Ruangan (Skenario 2 - Yang baru diperbaiki)
    // Penjelasan: Menggunakan Volt::route karena kir-detail-table adalah Volt Component
    Volt::route('/inventaris/ruangan/{ruangan}', 'inventaris.kir-detail-table')
        ->name('kir.ruangan.detail');

    /*
    |--------------------------------------------------------------------------
    | 3. ADMIN ONLY ROUTES (Gate access-admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['can:access-admin'])->group(function () {
        
        // Import SAP Data
        Route::get('/import-kcl', fn() => view('import-kcl'))->name('sap.kcl.import');
        Route::get('/import-asset', fn() => view('import-asset'))->name('sap.asset.import');

        // Manajemen Master Data
        Route::get('/katalog-barang', fn() => view('katalog.index'))->name('katalog.index');
        Route::get('/manajemen-barang', fn() => view('barang.index'))->name('barang.index');
        Route::get('/manajemen-lokasi', fn() => view('lokasi.index'))->name('lokasi.index');
        Route::get('/manajemen-sdm', fn() => view('sdm.index'))->name('sdm.index');

        // Laporan & Monitoring
        Route::get('/laporan-mutasi', fn() => view('laporan.mutasi'))->name('laporan.mutasi');
        Route::get('/data-kir', fn() => view('inventaris.index'))->name('data.kir');
        
        // Route ini sebelumnya tumpang tindih, disarankan gunakan 'kir.ruangan.detail' saja
        Route::get('/ruangan/{ruangan}/aset', function (Ruangan $ruangan) {
            return view('lokasi.kelola-aset', compact('ruangan'));
        })->name('ruangan.aset');
    });
});

require __DIR__.'/auth.php';