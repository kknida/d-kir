<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Ruangan;
use App\Models\Kir;


new class extends Component {
    #[Layout('layouts.blank')]
    public $kode_ruangan;

    public function mount($kode_ruangan)
    {
        $this->kode_ruangan = $kode_ruangan;
    }

    public function with(): array
    {
        $ruangan = Ruangan::with([
            'lantai.gedung.cabang', 
            'penanggungJawab', 
            'kirs.barang.sourceable', 
            'kirs.barang.tipe', 
            'kirs.barang.brand'
        ])->where('kode_ruangan', $this->kode_ruangan)->firstOrFail();

        // Logika Pengelompokan: Mengelompokkan barang berdasarkan nomor SAP (Asset Number atau Material)
        $groupedKirs = $ruangan->kirs->groupBy(function($item) {
            return $item->barang->sourceable->asset_number ?? $item->barang->sourceable->material;
        });

        return [
            'ruangan' => $ruangan,
            'groupedKirs' => $groupedKirs
        ];
    }
}; ?>

<div class="min-h-screen bg-white py-4 px-2 sm:px-6 font-sans print:p-0">
    <div class="max-w-4xl mx-auto p-4 sm:p-8 border border-slate-200 shadow-sm print:shadow-none print:border-none rounded-xl">
        
        <div class="relative flex items-center justify-center border-b-4 border-double border-slate-900 pb-4 mb-6">
            <div class="absolute left-0 flex-shrink-0">
                <img src="{{ asset('storage/logo-airnav.png') }}" alt="Logo" class="h-12 sm:h-16 w-auto">
            </div>
            
            <div class="text-center">
                <h1 class="text-lg sm:text-xl font-black uppercase tracking-widest text-slate-900">Kartu Inventaris Ruangan</h1>
                <p class="text-[10px] sm:text-xs font-bold mt-1 text-slate-500 uppercase italic">
                    Tanggal : <span class="ml-1">{{ now()->translatedFormat('d F Y') }}</span>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-4 mb-6 text-[11px] font-bold uppercase">
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 space-y-1">
                <div class="flex">
                    <span class="text-slate-400 w-24 flex-shrink-0">Gedung:</span> 
                    <span class="text-slate-900 ml-2">{{ $ruangan->lantai->gedung->nama }}</span>
                </div>
                <div class="flex">
                    <span class="text-slate-400 w-24 flex-shrink-0">Ruangan:</span> 
                    <span class="text-slate-900 ml-2">{{ $ruangan->nama }}</span>
                </div>
            </div>
            
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 space-y-1">
                <div class="flex">
                    <span class="text-slate-400 w-24 flex-shrink-0">Lantai:</span> 
                    <span class="text-slate-900 ml-2">{{ $ruangan->lantai->nama }}</span>
                </div>
                <div class="flex">
                    <span class="text-slate-400 w-24 flex-shrink-0">Kode Ruang:</span> 
                    <span class="text-slate-700 font-black ml-2">{{ $ruangan->kode_ruangan }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <div class="inline-block min-w-full align-middle px-4 sm:px-0">
                <table class="min-w-full border-collapse border border-slate-900 text-[10px] sm:text-[11px]">
                    <thead class="bg-slate-100 text-slate-900 font-bold uppercase">
                        <tr>
                            <th class="border border-slate-900 p-2 w-8 text-center">No</th>
                            <th class="border border-slate-900 p-2 text-center">Nama</th>
                            <th class="border border-slate-900 p-2 text-center">Merk / Type</th>
                            <th class="border border-slate-900 p-2 text-center">No. Asset / Material</th>
                            <!-- <th class="border border-slate-900 p-2 text-center">Kondisi</th> -->
                            <th class="border border-slate-900 p-2 w-16 text-center">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse($groupedKirs as $key => $items)
                        @php 
                            $firstItem = $items->first(); 
                            // 3. Kelompokkan jumlah barang
                            $totalQty = $items->count();
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="border border-slate-900 p-2 text-center font-bold">{{ $no++ }}</td>
                            <td class="border border-slate-900 p-2 leading-tight">
                                <span class="block font-black text-slate-800">{{ $firstItem->barang->tipe->nama }} {{ $firstItem->barang->brand->nama }} {{ $firstItem->barang->keterangan }}</span>
                                <!-- <span class="block font-black text-slate-800">{{ $firstItem->barang->sourceable->asset_name ?? $firstItem->barang->sourceable->material_description }}</span> -->
                                <span class="text-[9px] text-slate-400 italic">Catatan: {{ $firstItem->barang->sourceable->asset_name ?? $firstItem->barang->sourceable->material_description }}</span>
                            </td>
                            <td class="border border-slate-900 p-2 text-center uppercase">
                                {{ $firstItem->barang->brand->nama }} 
                            </td>
                            <td class="border border-slate-900 p-2 text-center font-mono font-bold text-indigo-700">
                                {{ $key }}
                            </td>
                            <!-- <td class="border border-slate-900 p-2 text-center">
                                @php
                                    $kodeKondisi = match($firstItem->kondisi) {
                                        'Baik' => 'B',
                                        'Rusak Ringan' => 'KB',
                                        'Rusak Berat' => 'RB',
                                        default => '-'
                                    };
                                @endphp
                                <span class="font-black px-2 py-0.5 rounded border border-slate-300">{{ $kodeKondisi }}</span>
                            </td> -->
                            <td class="border border-slate-900 p-2 text-center font-black text-[12px] bg-slate-50">
                                {{ $totalQty }} Unit
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="border border-slate-900 p-10 text-center text-slate-400 italic">Belum ada data barang di ruangan ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-[9px] font-bold text-slate-500 italic uppercase">
            <span>* B = Baik</span>
            <span>* KB = Kurang Baik (Rusak Ringan)</span>
            <span>* RB = Rusak Berat</span>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-8 text-center text-[11px]">
            <div class="space-y-16">
                <div>
                    <p class="font-bold">Mengetahui,</p>
                    <p class="font-black uppercase">Penanggung Jawab Ruangan</p>
                    <p class="uppercase">{{ $ruangan->penanggungJawab->jabatan->nama ?? '............................' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="font-black uppercase underline decoration-1">{{ $ruangan->penanggungJawab->nama ?? '............................' }}</p>
                    <p class="uppercase tracking-tighter">NIP: {{ $ruangan->penanggungJawab->nip ?? '..................' }}</p>
                </div>
            </div>
            <div class="space-y-16">
                <div>
                    <p class="font-bold">{{ now()->translatedFormat('d F Y') }}</p>
                    <p class="font-black uppercase">Petugas Inventaris</p>
                </div>
                <div class="space-y-1 text-center">
                    <p class="font-black underline decoration-1 italic text-slate-300">Digital Signature</p>
                    <p class="uppercase tracking-tighter text-[8px] text-slate-400 font-mono italic">Verified by System</p>
                </div>
            </div>
        </div>

        <div class="mt-12 pt-4 border-t border-slate-100 text-center">
            <p class="text-[8px] text-slate-300 font-mono uppercase tracking-[0.3em]">Smart KIR - Digital Room Inventory Asset Management</p>
        </div>
    </div>

    <div class="fixed bottom-6 left-1/2 -translate-x-1/2 flex gap-2 w-full px-4 sm:w-auto print:hidden">
        <button onclick="window.print()" class="flex-1 sm:flex-none flex items-center justify-center gap-2 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-xl active:scale-95 transition-all text-xs font-black uppercase tracking-widest">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak KIR
        </button>
    </div>
</div>