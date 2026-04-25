<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Ruangan;
use App\Models\Gedung;
use App\Models\PenanggungJawab;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $filterGedung = '';
    public $perPage = 10;

    // State Modal Barcode
    public $isQrModalOpen = false;
    public $selectedItem = null;

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterGedung() { $this->resetPage(); }

    // Fungsi untuk membuka modal dan mengambil data ruangan
    public function showQr($id)
    {
        $this->selectedItem = Ruangan::with(['lantai.gedung.cabang', 'penanggungJawab'])->findOrFail($id);
        $this->isQrModalOpen = true;
    }

    public function with(): array
    {
        $query = Ruangan::with(['lantai.gedung.cabang', 'penanggungJawab', 'kirs']);

        if ($this->search) {
            $query->where('nama', 'like', '%' . $this->search . '%')
                  ->orWhere('kode_ruangan', 'like', '%' . $this->search . '%');
        }

        if ($this->filterGedung) {
            $query->whereHas('lantai', function($q) {
                $q->where('gedung_id', $this->filterGedung);
            });
        }

        return [
            'ruangans' => $query->latest()->paginate($this->perPage),
            'gedungs' => Gedung::orderBy('nama')->get()
        ];
    }
}; ?>

<div class="space-y-6">
    <style>
        @media print {
            body * { visibility: hidden; }
            #printArea, #printArea * { visibility: visible; }
            #printArea { 
                position: absolute; 
                left: 0; 
                top: 0; 
                width: 100%;
                text-align: center;
            }
            .no-print { display: none !important; }
        }
    </style>

    <div class="flex flex-col md:flex-row gap-4 bg-white p-4 rounded-[2rem] border border-slate-200 shadow-sm no-print">
        <div class="relative flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" 
                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition-all" 
                   placeholder="Cari Nama Ruangan atau Kode...">
            <div class="absolute left-4 top-3.5 text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        <select wire:model.live="filterGedung" class="w-full md:w-64 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer font-bold text-slate-600">
            <option value="">Semua Gedung</option>
            @foreach($gedungs as $g)
                <option value="{{ $g->id }}">{{ $g->nama }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white border border-slate-200 rounded-[2rem] overflow-hidden shadow-sm no-print">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Cabang</th>
                        <th class="px-6 py-4">Gedung</th>
                        <th class="px-6 py-4">Nama Ruangan</th>
                        <th class="px-6 py-4">Penanggung Jawab (PIC)</th>
                        <th class="px-6 py-4 text-center">Total Aset</th>
                        <th class="px-6 py-4 text-center">Opsi</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($ruangans as $r)
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="text-slate-800">{{ optional(optional(optional($r->lantai)->gedung)->cabang)->nama ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-800">{{ optional(optional($r->lantai)->gedung)->nama ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800">{{ $r->nama }}</div>
                                <div class="text-[9px] text-slate-400 uppercase tracking-tighter">{{ optional($r->lantai)->nama }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($r->penanggungJawab)
                                    <div>
                                        <div class="text-xs font-bold text-slate-700">{{ $r->penanggungJawab->nama }}</div>
                                        <div class="text-[9px] text-slate-400 font-mono">NIP: {{ $r->penanggungJawab->nip }}</div>
                                    </div>
                                @else
                                    <span class="text-[10px] italic text-slate-400 font-bold uppercase">Belum ada PIC</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center justify-center min-w-[32px] h-8 px-2 bg-slate-100 rounded-xl text-xs font-black text-slate-600 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                    {{ $r->kirs->count() }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('ruangan.aset', $r->id) }}" 
                                   class="inline-flex items-center gap-2 bg-slate-900 hover:bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all shadow-md active:scale-95">
                                    <span>Buka KIR</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="showQr({{ $r->id }})" class="p-2 text-slate-600 hover:bg-slate-800 hover:text-white rounded-xl transition-all shadow-sm border border-slate-100 bg-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-20 text-center text-slate-400 font-bold uppercase tracking-widest text-xs">Data tidak ditemukan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
            {{ $ruangans->links() }}
        </div>
    </div>

    @if($isQrModalOpen && $selectedItem)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm no-print">
        <div class="bg-white rounded-[2.5rem] shadow-2xl p-8 max-w-sm w-full text-center relative border-4 border-indigo-500/20">
            <button wire:click="$set('isQrModalOpen', false)" class="absolute top-5 right-5 text-slate-400 hover:text-rose-500 bg-slate-50 p-2 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <div id="printArea" class="p-4 bg-white">
                <h3 class="font-black text-indigo-900 text-sm mb-1 uppercase tracking-tight">Label KIR Ruangan</h3>
                <p class="text-2xl font-bold text-slate-800 mb-6">{{ $selectedItem->nama }}</p>
                
                <div class="bg-white p-4 border-4 border-slate-100 rounded-[2rem] inline-block mb-6 shadow-sm">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode(url('/scan-ruangan/' . $selectedItem->kode_ruangan)) }}" 
                         alt="QR Code" class="w-48 h-48 mx-auto">
                </div>

                <div class="bg-indigo-50 text-indigo-700 py-3 px-6 rounded-2xl font-mono text-sm font-black break-all mb-6 border border-indigo-100 tracking-[0.2em]">
                    {{ $selectedItem->kode_ruangan }}
                </div>

                <div class="text-[10px] text-left text-slate-500 uppercase flex flex-col gap-2 bg-slate-50 p-4 rounded-2xl border border-slate-100 font-bold">
                    <div class="flex justify-between border-b border-slate-200 pb-1"><span>Lantai:</span> <span class="text-slate-800">{{ optional($selectedItem->lantai)->nama }}</span></div>
                    <div class="flex justify-between border-b border-slate-200 pb-1"><span>Gedung:</span> <span class="text-slate-800">{{ optional($selectedItem->lantai->gedung)->nama }}</span></div>
                    <div class="flex justify-between"><span>PIC:</span> <span class="text-indigo-600">{{ $selectedItem->penanggungJawab ? $selectedItem->penanggungJawab->nama : 'Belum Ada PIC' }}</span></div>
                </div>
            </div>

            <!-- <button onclick="window.print()" class="mt-6 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-indigo-200 active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                CETAK LABEL BARCODE
            </button> -->
        </div>
    </div>
    @endif
</div>