<?php

use Livewire\Volt\Component;
use App\Models\Ruangan;
use App\Models\Kir;
use Livewire\Attributes\Layout;
use App\Models\SapAsset;
use App\Models\SapKcl;

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

        return [
            'ruangan' => $ruangan,
        ];
    }
}; ?>

<div class="min-h-screen bg-slate-100 py-10 px-4 sm:px-6 lg:px-8 font-serif select-none print:bg-white print:p-0 print:py-0">
    <div class="max-w-5xl mx-auto bg-white shadow-2xl p-12 border border-slate-200 print:shadow-none print:border-none print:p-4">
        
        <div class="text-center border-b-4 border-double border-slate-900 pb-6 mb-8">
            <h1 class="text-2xl font-black uppercase tracking-widest text-slate-900">Daftar Inventaris Ruangan (KIR)</h1>
            <p class="text-sm font-bold mt-1 tracking-tight">TAHUN ANGGARAN: {{ date('Y') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-x-12 gap-y-2 mb-8 text-[12px] font-bold uppercase">
            <div class="space-y-1">
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="w-40 text-slate-500">GEDUNG / KANTOR</span>
                    <span class="mr-2">:</span>
                    <span class="flex-1 text-slate-900">{{ $ruangan->lantai->gedung->nama }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="w-40 text-slate-500">RUANGAN</span>
                    <span class="mr-2">:</span>
                    <span class="flex-1 text-slate-900">{{ $ruangan->nama }}</span>
                </div>
            </div>
            <div class="space-y-1">
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="w-40 text-slate-500">LANTAI / LEVEL</span>
                    <span class="mr-2">:</span>
                    <span class="flex-1 text-slate-900">{{ $ruangan->lantai->nama }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="w-40 text-slate-500">KODE RUANGAN</span>
                    <span class="mr-2">:</span>
                    <span class="flex-1 text-indigo-700 font-black">{{ $ruangan->kode_ruangan }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden border-2 border-slate-900">
            <table class="w-full border-collapse text-[11px]">
                <thead class="bg-slate-50 text-slate-900 font-black uppercase italic border-b-2 border-slate-900">
                    <tr>
                        <th class="border-r-2 border-slate-900 p-3 w-10 text-center">No</th>
                        <th class="border-r-2 border-slate-900 p-3 text-left">Nama Barang / Jenis Barang</th>
                        <th class="border-r-2 border-slate-900 p-3 text-left">Merk / Type</th>
                        <th class="border-r-2 border-slate-900 p-3 text-center">Kode Inventaris</th>
                        <th class="border-r-2 border-slate-900 p-3 w-32 text-center">Kondisi (B/KB/RB)</th>
                        <th class="p-3 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-slate-900">
                    @forelse($ruangan->kirs as $index => $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="border-r-2 border-slate-900 p-3 text-center font-bold">{{ $index + 1 }}</td>
                            <td class="border-r-2 border-slate-900 p-3 font-black text-slate-800">
                                {{ $item->barang->sourceable->asset_name ?? $item->barang->sourceable->material_description }}
                            </td>
                            <td class="border-r-2 border-slate-900 p-3">
                                {{ $item->barang->brand->nama }} / {{ $item->barang->tipe->nama }}
                            </td>
                            <td class="border-r-2 border-slate-900 p-3 text-center font-mono font-bold text-indigo-700">
                                {{ $item->barang->kode_inventaris }}
                            </td>
                            <td class="border-r-2 border-slate-900 p-3 text-center">
                                @php
                                    $kodeKondisi = match($item->kondisi) {
                                        'Baik' => 'B',
                                        'Rusak Ringan' => 'KB',
                                        'Rusak Berat' => 'RB',
                                        default => '-'
                                    };
                                    $warnaKondisi = match($item->kondisi) {
                                        'Baik' => 'bg-emerald-100 text-emerald-800',
                                        'Rusak Ringan' => 'bg-yellow-100 text-yellow-800',
                                        'Rusak Berat' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-slate-100 text-slate-800'
                                    };
                                @endphp
                                <span class="px-3 py-1 rounded-md font-black {{ $warnaKondisi }} border border-current print:bg-transparent print:border-none">
                                    {{ $kodeKondisi }}
                                </span>
                            </td>
                            <td class="p-3 italic text-slate-500 leading-tight">
                                {{ $item->barang->keterangan ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-400 font-bold italic">Belum ada aset terdaftar di ruangan ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex gap-6 text-[9px] font-bold text-slate-500 italic uppercase">
            <span>* Keterangan Kondisi:</span>
            <span>B = Baik</span>
            <span>KB = Kurang Baik (Rusak Ringan)</span>
            <span>RB = Rusak Berat</span>
        </div>

        <div class="mt-16 grid grid-cols-2 text-center text-xs">
            <div class="space-y-20">
                <div>
                    <p class="font-bold">Mengetahui,</p>
                    <p class="font-black uppercase">Penanggung Jawab Ruangan</p>
                </div>
                <div class="space-y-1">
                    <p class="font-black underline decoration-2">{{ $ruangan->penanggungJawab->nama ?? '.......................................' }}</p>
                    <p class="uppercase tracking-tighter">NIP: {{ $ruangan->penanggungJawab->nip ?? '........................' }}</p>
                </div>
            </div>
            <div class="space-y-20">
                <div>
                    <p class="font-bold">{{ now()->translatedFormat('d F Y') }}</p>
                    <p class="font-black uppercase">Petugas Inventaris</p>
                </div>
                <div class="space-y-1">
                    <p class="font-black underline decoration-2">.......................................</p>
                    <p class="uppercase tracking-tighter">NIP / PERSON ID: ........................</p>
                </div>
            </div>
        </div>

        <div class="mt-12 text-center print:hidden">
            <p class="text-[10px] text-slate-300 font-mono uppercase tracking-[0.5em]">Digital Room Inventory System - Verified</p>
        </div>
    </div>

    <div class="fixed bottom-8 right-8 flex flex-col gap-4 print:hidden">
        @guest
        <a href="/login" class="flex items-center gap-3 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-2xl hover:bg-slate-800 transition-all active:scale-95 group">
            <div class="p-2 bg-slate-700 rounded-lg group-hover:bg-indigo-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
            </div>
            <div class="text-left">
                <p class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-1">Staf Inventaris?</p>
                <p class="text-sm font-black tracking-tight leading-none">Masuk ke Sistem</p>
            </div>
        </a>
        @endguest

        <button onclick="window.print()" class="flex items-center gap-3 bg-indigo-600 text-white px-6 py-4 rounded-2xl shadow-2xl hover:bg-indigo-700 transition-all active:scale-95 group">
            <div class="p-2 bg-indigo-500 rounded-lg group-hover:bg-white group-hover:text-indigo-600 transition-colors text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </div>
            <div class="text-left">
                <p class="text-[10px] font-bold text-indigo-200 uppercase leading-none mb-1">Cetak Dokumen</p>
                <p class="text-sm font-black tracking-tight leading-none">Download PDF KIR</p>
            </div>
        </button>
    </div>
</div>