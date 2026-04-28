<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Ruangan;
use App\Models\Kir;
use App\Models\MutasiBarang;
use App\Models\Barang;
use App\Models\SapAsset;
use App\Models\SapKcl;

new class extends Component {
    use WithPagination, WithFileUploads;

    public Ruangan $ruangan; 
    
    public $search = '';
    public $perPage = 10;

    // State Modal
    public $isModalOpen = false;
    public $isQrModalOpen = false;
    public $isPhotoModalOpen = false;
    public $isKeluarkanModalOpen = false; 
    
    public $selectedItem = null;
    public $selectedPhotoUrl = null;

    // Form Fields (Check-In KIR)
    public $barang_id = '';
    public $kondisi = 'Baik';
    public $foto_kondisi_lokasi; 
    public $alasan_mutasi = '';  
    public $isMutasi = false;    
    public $alasan_histori = ''; 

    // Form Fields (Keluarkan Barang)
    public $keluarkan_kir_id = null;
    public $kondisi_keluar = 'Baik'; 
    public $alasan_keluar = '';

    public function mount(Ruangan $ruangan)
    {
        $this->ruangan = $ruangan;
    }

    public function updatedSearch() { $this->resetPage(); }

    public function openCreateModal()
    {
        $this->reset(['barang_id', 'foto_kondisi_lokasi', 'alasan_mutasi', 'isMutasi', 'alasan_histori']);
        $this->kondisi = 'Baik';
        $this->isModalOpen = true;
    }

    public function pilihBarang($id)
    {
        $this->barang_id = $id;
        $this->alasan_histori = ''; 

        $existingKir = Kir::where('barang_id', $id)->first();
        $this->isMutasi = $existingKir ? true : false;

        if ($this->isMutasi) {
            $this->kondisi = $existingKir->kondisi;
        } else {
            $lastMutasi = MutasiBarang::where('barang_id', $id)->latest()->first();
            
            if ($lastMutasi) {
                if (str_contains($lastMutasi->alasan_mutasi, 'Alasan: ')) {
                    $parts = explode('Alasan: ', $lastMutasi->alasan_mutasi);
                    $this->alasan_histori = end($parts);
                } else {
                    $this->alasan_histori = $lastMutasi->alasan_mutasi;
                }

                if (str_contains($lastMutasi->alasan_mutasi, 'Kondisi:')) {
                    if (str_contains($lastMutasi->alasan_mutasi, 'Rusak Berat')) {
                        $this->kondisi = 'Rusak Berat';
                    } elseif (str_contains($lastMutasi->alasan_mutasi, 'Rusak Ringan')) {
                        $this->kondisi = 'Rusak Ringan';
                    } else {
                        $this->kondisi = 'Baik';
                    }
                } else {
                    $this->kondisi = 'Baik';
                }
            } else {
                $this->kondisi = 'Baik';
            }
        }
    }

    public function showQr($id)
    {
        $this->selectedItem = Kir::with('barang.sourceable')->findOrFail($id);
        $this->isQrModalOpen = true;
    }

    public function showPhoto($id)
    {
        $kir = Kir::with('barang')->findOrFail($id);
        $this->selectedPhotoUrl = $kir->foto_kondisi_lokasi ? asset('storage/' . $kir->foto_kondisi_lokasi) : ($kir->barang->foto_barang ? asset('storage/' . $kir->barang->foto_barang) : null);
        $this->isPhotoModalOpen = true;
    }

    public function confirmKeluarkan($id)
    {
        $kir = Kir::findOrFail($id);
        $this->keluarkan_kir_id = $id;
        $this->kondisi_keluar = $kir->kondisi; 
        $this->alasan_keluar = '';
        $this->isKeluarkanModalOpen = true;
    }

    // Tambahkan fungsi ini di dalam bagian PHP (Volt)
    public function updateKondisiKIR()
    {
        $this->validate([
            'kondisi_keluar' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'alasan_keluar' => 'required|string|max:500'
        ]);

        $kir = Kir::with('barang')->findOrFail($this->keluarkan_kir_id);

        DB::transaction(function () use ($kir) {
            // 1. Update kondisi di tabel KIR (Data di Ruangan)
            $kir->update([
                'kondisi' => $this->kondisi_keluar,
            ]);

            // 2. Update status di tabel Barang (Data Master)
            // Disini kita masukkan nilai "Baik", "Rusak Ringan", atau "Rusak Berat"
            if ($kir->barang) {
                $kir->barang->update([
                    'status' => $this->kondisi_keluar, 
                    'is_active' => true,
                ]);
            }

            // 3. Catat histori mutasi
            MutasiBarang::create([
                'barang_id' => $kir->barang_id,
                'ruangan_asal_id' => $this->ruangan->id,
                'ruangan_tujuan_id' => $this->ruangan->id,
                'user_id' => auth()->id() ?? 1,
                'tanggal_mutasi' => now(),
                'alasan_mutasi' => "UPDATE STATUS MASTER: {$this->alasan_keluar} | Kondisi: {$this->kondisi_keluar}"
            ]);
        });

        $this->isKeluarkanModalOpen = false;
        $this->reset(['kondisi_keluar', 'alasan_keluar', 'keluarkan_kir_id']);
        
        session()->flash('success', 'Status master barang telah diperbarui menjadi ' . $this->kondisi_keluar);
    }

    public function prosesKeluarkan()
    {
        $this->validate([
            'kondisi_keluar' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'alasan_keluar' => 'required|string|max:500'
        ]);

        // Tambahkan with('barang') agar relasi terload dengan baik
        $kir = Kir::with('barang')->findOrFail($this->keluarkan_kir_id);

        DB::transaction(function () use ($kir) {
            // 1. Update status pada tabel Master Barang
            // Menyimpan status sesuai kondisi: Baik / Rusak Ringan / Rusak Berat
            if ($kir->barang) {
                $kir->barang->update([
                    'status' => $this->kondisi_keluar,
                    'is_active' => true, // Memastikan barang tetap aktif di sistem master
                ]);
            }

            // 2. Catat Histori Mutasi (Penarikan ke Gudang)
            MutasiBarang::create([
                'barang_id' => $kir->barang_id,
                'ruangan_asal_id' => $kir->ruangan_id,
                'ruangan_tujuan_id' => null, // null berarti kembali ke gudang/non-ruangan
                'user_id' => auth()->id() ?? 1,
                'tanggal_mutasi' => now(),
                'alasan_mutasi' => "DIKELUARKAN KE GUDANG | Kondisi: {$this->kondisi_keluar} | Alasan: {$this->alasan_keluar}"
            ]);

            // 3. Hapus data dari tabel KIR (Barang keluar dari ruangan tersebut)
            $kir->delete(); 
        });

        $this->isKeluarkanModalOpen = false;
        $this->reset(['kondisi_keluar', 'alasan_keluar', 'keluarkan_kir_id']);
        
        session()->flash('success', "Aset berhasil ditarik ke Gudang dengan status: {$this->kondisi_keluar}.");
    }

    //
    public function store()
    {
        $this->validate([
            'barang_id' => 'required|exists:barangs,id',
            'kondisi' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'foto_kondisi_lokasi' => 'nullable|image|max:2048',
            'alasan_mutasi' => $this->isMutasi ? 'required|string|max:500' : 'nullable|string',
        ]);

        $barang = Barang::findOrFail($this->barang_id);

        DB::transaction(function () use ($barang) {
            $existingKir = Kir::where('barang_id', $barang->id)->first();
            $path = $this->foto_kondisi_lokasi ? $this->foto_kondisi_lokasi->store('foto-lokasi-kir', 'public') : null;

            if ($existingKir) {
                // LOGIKA MUTASI ANTAR RUANGAN (asal_id diambil dari ruangan lama)
                MutasiBarang::create([
                    'barang_id' => $barang->id,
                    'ruangan_asal_id' => $existingKir->ruangan_id, // Ada asal ID nya
                    'ruangan_tujuan_id' => $this->ruangan->id,
                    'user_id' => auth()->id() ?? 1,
                    'tanggal_mutasi' => now(),
                    'alasan_mutasi' => $this->alasan_mutasi ?: 'Mutasi antar ruangan'
                ]);

                $existingKir->update([
                    'ruangan_id' => $this->ruangan->id,
                    'kondisi' => $this->kondisi,
                    'foto_kondisi_lokasi' => $path ?: $existingKir->foto_kondisi_lokasi, 
                ]);

            } else {
                // LOGIKA PENEMPATAN BARU (Jika DB tidak boleh NULL, arahkan ke ID 1 / Gudang)
                Kir::create([
                    'ruangan_id' => $this->ruangan->id,
                    'barang_id' => $barang->id,
                    'kondisi' => $this->kondisi,
                    'foto_kondisi_lokasi' => $path,
                ]);

                MutasiBarang::create([
                    'barang_id' => $barang->id,
                    'ruangan_asal_id' => $this->ruangan->id, // Jika DB paksa NOT NULL, isi sementara dengan ID ruangan ini atau ID 1
                    'ruangan_tujuan_id' => $this->ruangan->id,
                    'user_id' => auth()->id() ?? 1,
                    'tanggal_mutasi' => now(),
                    'alasan_mutasi' => $this->alasan_mutasi ?: 'Penempatan dari Gudang / Baru'
                ]);
            }
        });

        $this->isModalOpen = false;
        session()->flash('success', 'Aset berhasil di-check-in.');
    }

    public function with(): array
    {
        return [
            'items' => Kir::with(['barang.sourceable', 'barang.tipe.kategori', 'barang.brand', 'barang.mutasiTerakhir'])
                ->where('ruangan_id', $this->ruangan->id)
                ->whereHas('barang', function ($q) {
                    $q->where('kode_inventaris', 'like', "%{$this->search}%")
                      ->orWhereHasMorph('sourceable', [SapAsset::class, SapKcl::class], function ($qPoly, $type) {
                          if ($type === SapAsset::class) {
                              $qPoly->where('asset_name', 'like', "%{$this->search}%")->orWhere('asset_number', 'like', "%{$this->search}%");
                          } else {
                              $qPoly->where('material_description', 'like', "%{$this->search}%")->orWhere('material', 'like', "%{$this->search}%");
                          }
                      });
                })->latest()->paginate($this->perPage),

            // Memperbaiki pemanggilan variabel di Blade
            'pilihanBarang' => Barang::with(['tipe', 'brand', 'sourceable'])
                            ->where('status', '!=', 'hapus')
                            ->get(),

            'barangDenganHistori' => MutasiBarang::pluck('barang_id')->unique()->toArray(),
        ];
    }
}; ?>

<div class="space-y-4 relative">
    
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="text-sm font-bold">{{ session('success') }}</span>
        </div>
    @endif

    <div class="flex flex-col md:flex-row gap-4 justify-between items-center bg-white p-4 rounded-3xl border border-slate-200 shadow-sm">
        <div class="relative w-full md:w-96">
            <input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500" placeholder="Cari aset di ruangan ini...">
            <div class="absolute left-3 top-2.5 text-slate-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div>
        </div>
        <button wire:click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg flex items-center gap-2 text-sm transition-all active:scale-95">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Check-In Aset
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-[2rem] overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-5 py-4 text-center w-12">No</th>
                        <th class="px-5 py-4">Data SAP</th>
                        <th class="px-5 py-4">Nama, Spesifikasi & Kategori</th>
                        <th class="px-5 py-4 w-16 text-center">Foto</th>
                        <th class="px-5 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $index => $item)
                        <tr wire:key="kir-{{ $item->id }}" class="hover:bg-slate-50 transition-colors group">
                            <td class="px-5 py-3 text-center font-mono text-slate-400 text-xs">
                                {{ $items->total() - ($items->firstItem() + $index) + 1 }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="text-slate-800 line-clamp-1">
                                    {{ $item->barang->sourceable->asset_name ?? $item->barang->sourceable->material_description ?? 'Unnamed' }}
                                </div>
                                <div class="flex flex-col mt-1 gap-0.5">
                                    <span class="text-[10px] font-mono text-indigo-600 uppercase tracking-tighter italic font-bold">INV: {{ $item->barang->kode_inventaris }}</span>
                                    <span class="text-[10px] font-mono text-slate-500 uppercase tracking-tighter font-bold">
                                        {{ str_contains($item->barang->sourceable_type, 'Asset') ? 'No Asset' : 'No KCL' }}: 
                                        {{ $item->barang->sourceable->asset_number ?? $item->barang->sourceable->material }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="text-slate-700 font-bold text-l">{{ $item->barang->tipe->nama }} {{ $item->barang->brand->nama }} {{ $item->barang->keterangan }}</div>
                                <div class="text-[10px] text-indigo-500 font-bold uppercase mt-0.5">{{ $item->barang->tipe->kategori->nama ?? 'N/A' }}</div>
                                @php
                                    $style = match($item->kondisi) {
                                        'Baik' => 'text-emerald-600',
                                        'Rusak Ringan' => 'text-orange-500',
                                        'Rusak Berat' => 'text-rose-600',
                                        default => 'text-slate-400',
                                    };
                                @endphp
                                <div class="mt-1 flex items-center gap-1">
                                    <span class="text-[9px] text-slate-400 font-bold uppercase">Kondisi:</span>
                                    <span class="text-[9px] font-black uppercase {{ $style }}">{{ $item->kondisi }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button type="button" wire:click="showPhoto({{ $item->id }})" class="w-10 h-10 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 mx-auto hover:border-indigo-400 transition-all">
                                    <img src="{{ $item->foto_kondisi_lokasi ? asset('storage/' . $item->foto_kondisi_lokasi) : ($item->barang->foto_barang ? asset('storage/' . $item->barang->foto_barang) : '') }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                </button>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <div class="flex justify-center gap-1.5">
                                    <button wire:click="showQr({{ $item->id }})" class="p-2 text-slate-600 hover:bg-slate-800 hover:text-white rounded-xl transition-all border border-slate-100 shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg></button>
                                    <button wire:click="confirmKeluarkan({{ $item->id }})" class="p-2 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl transition-all border border-rose-50 shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 italic font-medium tracking-wide">Data tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">{{ $items->links() }}</div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-xl overflow-visible flex flex-col max-h-[90vh]">
            <div class="px-8 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-[2rem]">
                <div>
                    <h3 class="font-bold text-xl text-slate-800">Check-In Aset</h3>
                    <p class="text-xs text-indigo-600 font-bold mt-1">Ke: {{ $ruangan->nama }}</p>
                </div>
                <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-rose-500 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-8 overflow-y-auto">
                <form wire:submit="store" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Aset Fisik <span class="text-rose-500">*</span></label>
                        <div x-data="{
                            open: false, 
                            search: '',
                            get selectedName() {
                                        let items = @js($pilihanBarang);
                                        let found = items.find(i => i.id == $wire.barang_id);
                                        
                                        if(!found) return 'Cari Kode / Nama Aset...';

                                        // Menggunakan optional chaining (?.) untuk menghindari error jika null
                                        let tipe = found.tipe?.nama || '';
                                        let brand = found.brand?.nama || '';
                                        let ket = found.keterangan || '';

                                        // Gunakan trim() untuk membersihkan spasi berlebih jika tipe/brand kosong
                                        return (tipe + ' ' + brand + ' ' + ket).trim();
                                    }
                            }" @click.away="open = false" class="relative w-full">
                             
                            <button type="button" @click="open = !open" class="w-full text-left bg-white border-2 border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 flex justify-between items-center transition-all">
                                <span x-text="selectedName" class="truncate pr-4 font-bold text-slate-700"></span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            
                            <div x-show="open" style="display: none;" class="absolute z-[100] w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden">
                                <div class="p-2 border-b border-slate-100 bg-slate-50">
                                    <input x-model="search" type="text" class="w-full rounded-lg text-sm border-slate-200 focus:ring-indigo-500" placeholder="Ketik Kode / Nama...">
                                </div>
                                <ul class="max-h-56 overflow-y-auto p-1 bg-white">
                                    @foreach($pilihanBarang as $b)
                                        @php $punyaHistori = in_array($b->id, $barangDenganHistori); @endphp
                                        <li x-show="'{{ strtolower($b->kode_inventaris . ' ' . (optional($b->sourceable)->asset_name ?? optional($b->sourceable)->material_description)) }}'.includes(search.toLowerCase())" 
                                            @click="$wire.pilihBarang({{ $b->id }}); open = false" 
                                            class="px-4 py-3 text-sm hover:bg-indigo-50 cursor-pointer rounded-lg transition-colors border-b border-slate-50 last:border-0">
                                            <div class="flex justify-between items-start gap-2">
                                                <div class="flex-1">
                                                    <div class="font-bold text-indigo-700 font-mono text-xs mb-0.5">{{ $b->sourceable->material ?? $b->sourceable->asset_number ?? 'Aset' }} | {{ $b->kode_inventaris }}</div>
                                                    <div class="text-slate-700 font-medium leading-tight text-xs">{{ $b->tipe->nama }} {{ $b->brand->nama }} {{ $b->keterangan }}</div>
                                                </div>
                                                @if($b->lokasiTerkini && $b->lokasiTerkini->ruangan)
                                                    <div class="shrink-0 flex flex-col items-end">
                                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[8px] font-black uppercase tracking-wider rounded border border-amber-200">Mutasi Dari</span>
                                                        <span class="text-[9px] text-amber-600 font-bold mt-0.5 text-right w-24 truncate">{{ $b->lokasiTerkini->ruangan->nama }}</span>
                                                    </div>
                                                @elseif($punyaHistori)
                                                    <span class="shrink-0 px-2 py-1 bg-slate-100 text-slate-700 text-[9px] font-black uppercase tracking-wider rounded-md border border-slate-200 mt-1">Gudang / Transit</span>
                                                @else
                                                    <span class="shrink-0 px-2 py-1 bg-emerald-100 text-emerald-700 text-[9px] font-black uppercase tracking-wider rounded-md border border-emerald-200 mt-1">Barang Baru</span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @error('barang_id') <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    @if(!$isMutasi && $konditionValue = $kondisi !== 'Baik' && $barang_id)
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex gap-3 shadow-sm">
                            <svg class="w-5 h-5 shrink-0 text-amber-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            <div>
                                <span class="font-bold uppercase tracking-tight">Peringatan Histori:</span> Aset ini terakhir ditarik ke gudang dalam kondisi <b class="text-rose-600 uppercase underline decoration-rose-300 decoration-2">{{ $kondisi }}</b> karena: <br>
                                <span class="block mt-1 p-2 bg-white/60 rounded-lg italic border border-amber-200 font-medium">"{{ $alasan_histori }}"</span>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Kondisi Fisik Saat Masuk</label>
                            <div class="flex flex-col gap-2">
                                <label class="cursor-pointer"><input type="radio" wire:model="kondisi" value="Baik" class="peer sr-only"><div class="py-2.5 border-2 border-slate-100 rounded-xl text-center text-xs font-bold text-slate-500 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 transition-all">Baik</div></label>
                                <label class="cursor-pointer"><input type="radio" wire:model="kondisi" value="Rusak Ringan" class="peer sr-only"><div class="py-2.5 border-2 border-slate-100 rounded-xl text-center text-xs font-bold text-slate-500 peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 transition-all">Rusak Ringan</div></label>
                                <label class="cursor-pointer"><input type="radio" wire:model="kondisi" value="Rusak Berat" class="peer sr-only"><div class="py-2.5 border-2 border-slate-100 rounded-xl text-center text-xs font-bold text-slate-500 peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 transition-all">Rusak Berat</div></label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Foto Kondisi Lokasi</label>
                            <label class="flex flex-col items-center justify-center w-full h-full min-h-[140px] border-2 border-dashed border-slate-200 rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 relative overflow-hidden transition-all group">
                                @if($foto_kondisi_lokasi)
                                    <img src="{{ $foto_kondisi_lokasi->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                                @else
                                    <div class="flex flex-col items-center text-slate-400 group-hover:text-indigo-500 transition-colors">
                                        <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                        <span class="text-[10px] font-bold uppercase tracking-widest">Klik Kamera</span>
                                    </div>
                                @endif
                                <input type="file" wire:model="foto_kondisi_lokasi" accept="image/*" capture="environment" class="hidden" />
                            </label>
                            @error('foto_kondisi_lokasi') <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if($isMutasi)
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl">
                        <label class="block text-xs font-bold text-amber-800 mb-2">Alasan Mutasi (Aset dari Ruangan Lain) <span class="text-rose-500">*</span></label>
                        <textarea wire:model="alasan_mutasi" rows="2" class="w-full rounded-xl border-amber-200 focus:ring-amber-500 text-sm bg-white" placeholder="Sebutkan alasan pemindahan..."></textarea>
                        @error('alasan_mutasi') <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>
                    @else
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Catatan (Opsional)</label>
                        <textarea wire:model="alasan_mutasi" rows="2" class="w-full rounded-xl border-slate-200 focus:ring-indigo-500 text-sm" placeholder="Catatan penempatan..."></textarea>
                    </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-6 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Batal</button>
                        <button type="submit" class="px-8 py-2.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all active:scale-95">Check-In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($isQrModalOpen && $selectedItem)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl p-8 max-w-sm w-full text-center relative">
            <button wire:click="$set('isQrModalOpen', false)" class="absolute top-4 right-4 text-slate-400 hover:text-rose-500 bg-slate-50 p-1.5 rounded-full"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            <h3 class="font-bold text-slate-800 text-lg mb-1">QR Code Scannable</h3>
            <p class="text-xs text-slate-500 mb-6 font-medium">{{ $selectedItem->barang->sourceable->asset_name ?? $selectedItem->barang->sourceable->material_description ?? 'Aset' }}</p>
            <div class="bg-white p-4 border-4 border-slate-100 rounded-3xl inline-block mb-4 shadow-sm">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode(route('scan.barang', $selectedItem->barang->kode_inventaris)) }}" alt="QR Code Link" class="w-48 h-48">
            </div>
            <div class="bg-indigo-50 text-indigo-700 py-2.5 px-4 rounded-xl font-mono text-sm font-bold break-all mb-2 border border-indigo-100">{{ $selectedItem->barang->kode_inventaris }}</div>
        </div>
    </div>
    @endif

    @if($isPhotoModalOpen)
    <div class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" x-data @keydown.escape.window="$wire.set('isPhotoModalOpen', false)" @click.self="$wire.set('isPhotoModalOpen', false)">
        <button wire:click="$set('isPhotoModalOpen', false)" class="absolute top-6 right-6 text-white/50 hover:text-white transition-colors bg-white/10 hover:bg-white/20 p-2 rounded-full"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        @if($selectedPhotoUrl)
            <img src="{{ $selectedPhotoUrl }}" class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl pointer-events-none">
        @else
            <div class="bg-slate-800 p-8 rounded-2xl flex flex-col items-center justify-center text-slate-400"><svg class="w-16 h-16 mb-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg><p>Foto tidak tersedia</p></div>
        @endif
    </div>
    @endif

    @if($isKeluarkanModalOpen)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
            <div class="px-6 py-5 border-b border-rose-100 bg-blue-50 flex items-center gap-3">
                <!-- <div class="p-2 bg-rose-100 rounded-full text-rose-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </div> -->
                <div>
                    <h3 class="font-bold text-lg text-blue-900 uppercase">Manajemen Kondisi Aset</h3>
                    <p class="text-[10px] text-blue-600 font-bold uppercase tracking-tighter italic">Pilih simpan untuk update atau tarik untuk ke gudang</p>
                </div>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <div class="space-y-5">
                    {{-- 1. Penyesuaian Warna Kondisi --}}
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-3">Kondisi Fisik Saat Ini <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach([
                                'Baik' => 'peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700',
                                'Rusak Ringan' => 'peer-checked:border-yellow-500 peer-checked:bg-yellow-50 peer-checked:text-yellow-700',
                                'Rusak Berat' => 'peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700'
                            ] as $label => $colorClass)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="kondisi_keluar" value="{{ $label }}" class="peer sr-only">
                                    <div class="py-3 border-2 border-slate-100 rounded-xl text-center text-[10px] font-black uppercase text-slate-400 {{ $colorClass }} transition-all shadow-sm">
                                        {{ $label }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Keterangan / Alasan <span class="text-rose-500">*</span></label>
                        <textarea wire:model="alasan_keluar" rows="3" class="w-full rounded-xl border-slate-200 focus:ring-indigo-500 text-sm shadow-sm" placeholder="Jelaskan detail kondisi atau alasan penarikan..."></textarea>
                        @error('alasan_keluar') <span class="text-xs text-rose-500 mt-1 block font-bold italic">{{ $message }}</span> @enderror
                    </div>
                    
                    {{-- 2. Tiga Tombol Aksi --}}
                    <div class="flex flex-col gap-3 pt-5 border-t border-slate-100">
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="$set('isKeluarkanModalOpen', false)" class="py-3 text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors uppercase tracking-widest">
                                Batal
                            </button>
                            
                            <button type="button" wire:click="updateKondisiKIR" wire:loading.attr="disabled" class="py-3 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 rounded-xl transition-all uppercase tracking-widest">
                                <span wire:loading.remove wire:target="updateKondisiKIR">Simpan Kondisi</span>
                                <span wire:loading wire:target="updateKondisiKIR">...</span>
                            </button>
                        </div>

                        <button type="button" wire:click="prosesKeluarkan" wire:loading.attr="disabled" class="w-full py-4 text-xs font-black text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-all shadow-lg shadow-rose-100 flex items-center justify-center gap-2 uppercase tracking-[0.2em]">
                            <span wire:loading.remove wire:target="prosesKeluarkan">Proses Tarik ke Gudang</span>
                            <span wire:loading wire:target="prosesKeluarkan">Memproses Penarikan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>