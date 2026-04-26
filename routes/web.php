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
// Ganti /scan/ruangan/ menjadi /scan-ruangan/
Route::get('/scan-ruangan/{kode_ruangan}', function ($kode_ruangan) {
    // Cari ruangan berdasarkan kode, jika tidak ada langsung munculkan 404
    $ruangan = App\Models\Ruangan::where('kode_ruangan', $kode_ruangan)->firstOrFail();

    if (Auth::check()) {
        // Skenario 2: Jika petugas sudah login, masuk ke manajemen inventarisasi
        return redirect()->route('kir.ruangan.detail', $ruangan->id);
    }

    // Skenario 1: Jika guest (scan pake HP biasa), masuk ke tampilan dokumen publik
    return redirect()->route('public.kir.view', $ruangan->kode_ruangan);
})->name('scan.ruangan.gate');

// === INI YANG HILANG SEBELUMNYA ===
// Tampilan KIR Publik (Skenario 1)
Volt::route('/public/kir/{kode_ruangan}', 'inventaris.public-kir-view')
    ->name('public.kir.view');
// ==================================

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
        
        Route::get('/scan/{kode}', function ($kode) {
            $barang = App\Models\Barang::with(['sourceable', 'tipe', 'brand'])
                        ->where('kode_inventaris', $kode)
                        ->firstOrFail();
                        
            return view('barang.scan', compact('barang'));
        })->name('scan.barang');

        // Route ini sebelumnya tumpang tindih, disarankan gunakan 'kir.ruangan.detail' saja
        Route::get('/ruangan/{ruangan}/aset', function (Ruangan $ruangan) {
            return view('lokasi.kelola-aset', compact('ruangan'));
        })->name('ruangan.aset');
    });
});

require __DIR__.'/auth.php';