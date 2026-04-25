<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Barang;
use App\Models\Kir;
use App\Models\MutasiBarang;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] class extends Component {

    public function with(): array
    {
        // 1. Ambil ID Barang yang saat ini ada di ruangan (KIR)
        $barangInKirIds = Kir::pluck('barang_id')->toArray();

        // 2. Hitung kondisi dari aset yang AKTIF di ruangan
        $kondisiBaik = Kir::where('kondisi', 'Baik')->count();
        $kondisiRusakRingan = Kir::where('kondisi', 'Rusak Ringan')->count();
        $kondisiRusakBeratKir = Kir::where('kondisi', 'Rusak Berat')->count();

        // 3. Hitung aset di GUDANG yang terakhir kali dicatat Rusak Berat
        // Kita cek di MutasiBarang untuk barang yang TIDAK ADA di KIR
        $kondisiRusakBeratGudang = MutasiBarang::whereNotIn('barang_id', $barangInKirIds)
            ->whereIn('id', function($query) {
                $query->select(DB::raw('MAX(id)'))
                      ->from('mutasi_barangs')
                      ->groupBy('barang_id');
            })
            ->where('alasan_mutasi', 'like', '%Kondisi: Rusak Berat%')
            ->count();

        return [
            'totalAset' => Barang::count(),
            'kondisiBaik' => $kondisiBaik,
            'kondisiRusakRingan' => $kondisiRusakRingan,
            'kondisiRusakBerat' => $kondisiRusakBeratKir + $kondisiRusakBeratGudang, // Gabungan Ruangan + Gudang
            
            'recentMutations' => MutasiBarang::with([
                    'barang.sourceable',
                    'ruanganAsal',
                    'ruanganTujuan'
                ])
                ->latest('tanggal_mutasi')
                ->limit(5)
                ->get(),
        ];
    }
};
?>

<!-- ✅ WAJIB: SATU ROOT ELEMENT -->
<div class="p-4 md:p-6 space-y-6">

    <!-- HEADER -->
    <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">
            Ringkasan Inventaris
        </h2>
        <p class="text-slate-500">
            Monitor aset AirNav Indonesia secara real-time.
        </p>
    </div>

    <!-- CARD SUMMARY -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- TOTAL ASET -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                    </path>
                </svg>
            </div>
            <div>
                <p class="text-slate-500 text-sm font-medium">Total Aset</p>
                <h3 class="text-2xl font-extrabold text-slate-800">
                    {{ number_format($totalAset) }}
                </h3>
            </div>
        </div>

        <!-- KONDISI BAIK -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
            </div>
            <div>
                <p class="text-slate-500 text-sm font-medium">Kondisi Baik</p>
                <h3 class="text-2xl font-extrabold text-slate-800">
                    {{ number_format($kondisiBaik) }}
                </h3>
            </div>
        </div>

        <!-- RUSAK RINGAN -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
            </div>
            <div>
                <p class="text-slate-500 text-sm font-medium">Rusak Ringan</p>
                <h3 class="text-2xl font-extrabold text-slate-800">
                    {{ number_format($kondisiRusakRingan) }}
                </h3>
            </div>
        </div>

        <!-- RUSAK BERAT -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
            </div>
            <div>
                <p class="text-slate-500 text-sm font-medium">Rusak Berat</p>
                <h3 class="text-2xl font-extrabold text-slate-800">
                    {{ number_format($kondisiRusakBerat) }}
                </h3>
            </div>
        </div>

    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">

        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-slate-800">Mutasi Aset Terbaru</h3>
            <a href="{{ route('laporan.mutasi') }}"
               class="text-sm font-bold text-blue-600 hover:underline">
                Lihat Semua
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">

                <thead class="bg-slate-50 border-b text-xs uppercase text-slate-400 font-semibold">
                    <tr>
                        <th class="px-6 py-4 text-center">No</th>
                        <th class="px-6 py-4">Nama Barang</th>
                        <th class="px-6 py-4">Asal</th>
                        <th class="px-6 py-4 text-center"></th>
                        <th class="px-6 py-4">Tujuan</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y text-sm">
                    @forelse($recentMutations as $i => $mutasi)

                        @php
                            $isPenarikan = str_contains(strtoupper($mutasi->alasan_mutasi), 'DIKELUARKAN');
                            $isBaru = str_contains(strtoupper($mutasi->alasan_mutasi), 'AWAL');
                        @endphp

                        <tr class="hover:bg-slate-50">

                            <td class="px-6 py-4 text-center">
                                {{ $i + 1 }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-bold">
                                    {{ $mutasi->barang->sourceable->asset_name
                                        ?? $mutasi->barang->sourceable->material_description
                                        ?? 'Aset' }}
                                </div>
                                <div class="text-xs text-indigo-500 font-mono">
                                    {{ $mutasi->barang->kode_inventaris }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                {{ $isBaru ? 'Penerimaan Baru' : ($mutasi->ruanganAsal->nama ?? 'Gudang') }}
                            </td>

                            <td class="text-center">→</td>

                            <td class="px-6 py-4">
                                {{ $isPenarikan ? 'Gudang / Transit' : ($mutasi->ruanganTujuan->nama ?? '-') }}
                            </td>

                            <td class="px-6 py-4">
                                {{ \Carbon\Carbon::parse($mutasi->tanggal_mutasi)->translatedFormat('d M Y') }}
                            </td>

                            <td class="px-6 py-4">
                                @if($isPenarikan)
                                    <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded text-xs">Penarikan</span>
                                @elseif($isBaru)
                                    <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs">Distribusi</span>
                                @else
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Mutasi</span>
                                @endif
                            </td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400">
                                Belum ada data
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</div>