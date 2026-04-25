<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use App\Models\Barang;
use App\Models\Tipe;
use App\Models\Brand;
use App\Models\SapAsset;
use App\Models\SapKcl;
use App\Models\MutasiBarang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 10;

    // State Modal
    public $isModalOpen = false;
    public $isQrModalOpen = false;
    public $isPhotoModalOpen = false; 
    public $isDetailModalOpen = false; 
    
    public $selectedItem = null;
    public $detailItem = null; 
    public $selectedPhotoUrl = null; 

    // Form Fields
    public $kode_inventaris, $tipe_id, $brand_id, $keterangan, $foto_barang, $is_active = true;
    public $source_type = ''; 
    public $source_id = '';

    public function updatedSearch() { $this->resetPage(); }
    public function updatedSourceType() { $this->source_id = ''; }

    public function openCreateModal()
    {
        $this->reset(['kode_inventaris', 'tipe_id', 'brand_id', 'keterangan', 'foto_barang', 'source_type', 'source_id']);
        $this->kode_inventaris = 'INV' . date('ymd') . strtoupper(Str::random(4));
        $this->isModalOpen = true;
    }

    public function showQr($id)
    {
        $this->selectedItem = Barang::findOrFail($id);
        $this->isQrModalOpen = true;
    }

    public function showPhoto($id)
    {
        $item = Barang::find($id);
        $this->selectedPhotoUrl = ($item && $item->foto_barang) ? asset('storage/' . $item->foto_barang) : null;
        $this->isPhotoModalOpen = true;
    }

    public function showDetail($id)
    {
        $this->detailItem = Barang::with(['sourceable', 'tipe.kategori', 'brand', 'mutasiBarangs' => fn($q) => $q->latest('tanggal_mutasi')])->findOrFail($id);
        $this->isDetailModalOpen = true;
    }

    public function delete($id)
    {
        $barang = Barang::with('sourceable')->findOrFail($id);
        DB::transaction(function () use ($barang) {
            $barang->update(['status' => 'hapus', 'is_active' => false]);
            $source = $barang->sourceable;
            if ($source) {
                if ($barang->sourceable_type === 'App\Models\SapKcl' && isset($source->unrestricted)) {
                    $source->decrement('unrestricted', 1);
                } elseif ($barang->sourceable_type === 'App\Models\SapAsset' && isset($source->quantity)) {
                    $source->decrement('quantity', 1);
                }
            }
        });
        session()->flash('success', 'Barang berhasil dihapus.');
    }

    public function store()
    {
        $sapItem = null;
        $totalRegistered = 0;
        $sapLimit = 0;

        if ($this->source_type === 'App\Models\SapAsset') {
            $sapItem = SapAsset::find($this->source_id);
            if ($sapItem) {
                $sapLimit = (int) $sapItem->quantity;
                // Hitung barang yang sudah terdaftar dengan Asset Number & Sub Number yang sama
                $totalRegistered = Barang::whereHasMorph('sourceable', [SapAsset::class], function($q) use ($sapItem) {
                    $q->where('asset_number', $sapItem->asset_number)
                    ->where('sub_number', $sapItem->sub_number);
                })->where('status', '!=', 'hapus')->count();
            }
        } else if ($this->source_type === 'App\Models\SapKcl') {
            $sapItem = SapKcl::find($this->source_id);
            if ($sapItem) {
                $sapLimit = (int) ($sapItem->quantity ?? $sapItem->unrestricted ?? 0);
                // Hitung barang yang sudah terdaftar dengan Kode Material yang sama
                $totalRegistered = Barang::whereHasMorph('sourceable', [SapKcl::class], function($q) use ($sapItem) {
                    $q->where('material', $sapItem->material);
                })->where('status', '!=', 'hapus')->count();
            }
        }

        // 2. Validasi Kuota SAP
        if ($sapItem && $totalRegistered >= $sapLimit) {
            $this->addError('source_id', "Gagal! Jumlah barang terdaftar ($totalRegistered) sudah mencapai batas kuantitas SAP ($sapLimit).");
            return;
        }

        // 3. Validasi Semua Input Wajib Diisi
        $this->validate([
            'source_type' => 'required',
            'source_id'   => 'required',
            'tipe_id'     => 'required',
            'brand_id'    => 'required',
            'keterangan'  => 'required',
            'foto_barang' => 'required|image|max:2048',
        ], [
            'required' => 'Bidang ini wajib diisi.',
            'foto_barang.required' => 'Foto fisik barang wajib diunggah.',
        ]);

        $path = $this->foto_barang->store('foto-barang', 'public');

        Barang::create([
            'kode_inventaris' => $this->kode_inventaris,
            'sourceable_type' => $this->source_type,
            'sourceable_id' => $this->source_id,
            'tipe_id' => $this->tipe_id,
            'brand_id' => $this->brand_id,
            'keterangan' => $this->keterangan,
            'foto_barang' => $path,
            'is_active' => $this->is_active,
            'status' => 'tersedia',
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Barang berhasil diregistrasi.');
    }

    public function with(): array
    {
        $query = Barang::with(['sourceable', 'tipe.kategori', 'brand', 'mutasiTerakhir'])
                        ->where('status', '!=', 'hapus');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('kode_inventaris', 'like', "%{$this->search}%")
                  ->orWhere('keterangan', 'like', "%{$this->search}%")
                  ->orWhereHas('tipe', fn($qT) => $qT->where('nama', 'like', "%{$this->search}%"))
                  ->orWhereHas('tipe.kategori', fn($qK) => $qK->where('nama', 'like', "%{$this->search}%"))
                  ->orWhereHas('mutasiBarangs', fn($qM) => $qM->where('alasan_mutasi', 'like', "%{$this->search}%"))
                  ->orWhereHasMorph('sourceable', [SapAsset::class, SapKcl::class], function ($qPoly, $type) {
                      if ($type === SapAsset::class) {
                          $qPoly->where('asset_number', 'like', "%{$this->search}%")
                                ->orWhere('asset_name', 'like', "%{$this->search}%");
                      } elseif ($type === SapKcl::class) {
                          $qPoly->where('material', 'like', "%{$this->search}%")
                                ->orWhere('material_description', 'like', "%{$this->search}%");
                      }
                  });
            });
        }

        $registeredQty = 0; $totalQty = 0;
        if ($this->source_type && $this->source_id) {
            $registeredQty = Barang::where('sourceable_type', $this->source_type)->where('sourceable_id', $this->source_id)->where('status', '!=', 'hapus')->count();
            if ($this->source_type === 'App\Models\SapAsset') {
                $asset = SapAsset::find($this->source_id); $totalQty = $asset ? max(0, (int)$asset->quantity) : 0; 
            } elseif ($this->source_type === 'App\Models\SapKcl') {
                $kcl = SapKcl::find($this->source_id); $totalQty = $kcl ? max(0, (int)$kcl->unrestricted) : 0;
            }
        }

        return [
            'items' => $query->latest()->paginate($this->perPage),
            'tipes' => Tipe::all(),
            'brands' => Brand::all(),
            'assets' => SapAsset::all(),
            'kcls' => SapKcl::all(),
            'registeredQty' => $registeredQty,
            'totalQty' => $totalQty,
        ];
    }
}; ?>

<div class="space-y-4 relative">
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="text-sm font-bold">{{ session('success') }}</span>
        </div>
    @endif

    <div class="flex flex-col md:flex-row gap-4 justify-between items-center bg-white p-4 rounded-3xl border border-slate-200 shadow-sm">
        <div class="relative w-full md:w-[500px]">
            <input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500" placeholder="Cari Kode Inv, No Asset, Nama Barang, Kategori, Kondisi...">
            <div class="absolute left-3.5 top-3 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        <button wire:click="openCreateModal" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg flex items-center gap-2 text-sm transition-all active:scale-95">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Registrasi Barang
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
                        <th class="px-5 py-4">Status SAP</th>
                        <th class="px-5 py-4 w-16 text-center">Foto</th>
                        <th class="px-5 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $index => $item)
                        <tr wire:key="barang-{{ $item->id }}" class="hover:bg-slate-50 transition-colors group">
                            <td class="px-5 py-3 text-center font-mono text-slate-400 text-xs">
                                {{ $items->total() - ($items->firstItem() + $index) + 1 }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="text-slate-700 text-xs">
                                    {{ $item->sourceable->asset_name ?? $item->sourceable->material_description ?? 'Unnamed' }}
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[13px] font-mono text-slate-500 uppercase tracking-tighter font-bold">
                                        {{ str_contains($item->sourceable_type, 'Asset') ? 'No Asset' : 'No KCL' }}: 
                                        {{ $item->sourceable->asset_number ?? $item->sourceable->material }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-bold text-slate-800 line-clamp-1">{{ $item->tipe->nama }} {{ $item->brand->nama }} {{ $item->keterangan }} </div>
                                <div class="text-[10px] text-indigo-500 font-bold uppercase mt-0.5">{{ $item->tipe->kategori->nama ?? 'N/A' }}</div>
                                
                                {{-- PERBAIKAN LOGIKA KONDISI & WARNA (POIN 1 & 2) --}}
                                @php
                                    $rawKondisi = $item->mutasiTerakhir->alasan_mutasi ?? '';
                                    $kondisi = 'Baru';
                                    if (str_contains($rawKondisi, 'Kondisi: Baik')) $kondisi = 'Baik';
                                    elseif (str_contains($rawKondisi, 'Kondisi: Rusak Ringan')) $kondisi = 'Rusak Ringan';
                                    elseif (str_contains($rawKondisi, 'Kondisi: Rusak Berat')) $kondisi = 'Rusak Berat';

                                    $colorClass = match($kondisi) {
                                        'Baik' => 'text-emerald-600',
                                        'Rusak Ringan' => 'text-orange-500', // Sesuai permintaan: orange
                                        'Rusak Berat' => 'text-rose-600',   // Sesuai permintaan: merah
                                        default => 'text-slate-400',       // Sesuai permintaan: abu
                                    };
                                @endphp
                                <div class="mt-1 flex items-center gap-1">
                                    <span class="text-[10px] font-mono text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100 tracking-tighter">Kode: {{ $item->kode_inventaris }}</span>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase">Kondisi:</span>
                                    <span class="text-[9px] font-black uppercase {{ $colorClass }}">{{ $kondisi }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="text-[10px] text-slate-400 font-bold">Qty SAP</div>
                                <div class="text-sm font-black text-slate-700">{{ $item->sourceable->quantity ?? $item->sourceable->unrestricted ?? 0 }} <span class="text-[10px] font-normal text-slate-400">Unit</span></div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button type="button" wire:click="showPhoto({{ $item->id }})" class="w-10 h-10 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 mx-auto">
                                    @if($item->foto_barang)
                                        <img src="{{ asset('storage/' . $item->foto_barang) }}" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-5 h-5 text-slate-300 mx-auto mt-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path></svg>
                                    @endif
                                </button>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-center gap-1.5">
                                    <button wire:click="showDetail({{ $item->id }})" class="p-2 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all border border-blue-50 shadow-sm" title="Detil Lengkap">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                    <button wire:click="showQr({{ $item->id }})" class="p-2 text-slate-600 hover:bg-slate-800 hover:text-white rounded-xl transition-all border border-slate-100 shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg></button>
                                    <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus barang ini secara logis (Ubah Status ke HAPUS)?" class="p-2 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl transition-all border border-rose-50 shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400 italic font-medium tracking-wide">Data tidak ditemukan atau belum ada registrasi aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">{{ $items->links() }}</div>
    </div>

    @if($isDetailModalOpen && $detailItem)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-4xl flex flex-col max-h-[90vh] overflow-hidden">
            <div class="px-8 py-5 border-b bg-slate-50 flex justify-between items-center">
                <h3 class="font-black text-xl text-slate-800 uppercase tracking-tight">Detail Inventaris Lengkap</h3>
                <button wire:click="$set('isDetailModalOpen', false)" class="text-slate-400 hover:text-rose-500 transition-colors"><svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <div class="p-8 overflow-y-auto grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-6">
                    <div class="aspect-square rounded-[2rem] bg-slate-100 border overflow-hidden flex items-center justify-center">
                        @if($detailItem->foto_barang) <img src="{{ asset('storage/'.$detailItem->foto_barang) }}" class="w-full h-full object-cover"> @else <span class="text-slate-300 italic text-xs">No Photo</span> @endif
                    </div>
                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl text-center">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $detailItem->kode_inventaris }}" class="mx-auto w-24 mb-2">
                        <div class="text-[10px] font-mono font-bold text-blue-700 uppercase tracking-widest">{{ $detailItem->kode_inventaris }}</div>
                    </div>
                </div>
                <div class="md:col-span-2 space-y-6">
                    <div class="space-y-3">
                        <h4 class="text-xs font-black text-blue-600 uppercase tracking-widest flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-600"></span> Data Sumber SAP</h4>
                        <div class="grid grid-cols-2 gap-4 bg-slate-50 p-5 rounded-2xl border border-slate-200">
                            @foreach($detailItem->sourceable->toArray() as $key => $value)
                                @if(!is_array($value))
                                    <div><div class="text-[9px] uppercase font-bold text-slate-400">{{ str_replace('_', ' ', $key) }}</div><div class="text-xs font-bold text-slate-700">{{ $value ?: '-' }}</div></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="space-y-3">
                        <h4 class="text-xs font-black text-indigo-600 uppercase tracking-widest flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-indigo-600"></span> Spesifikasi Barang</h4>
                        <div class="grid grid-cols-2 gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <div><div class="text-[9px] font-bold text-slate-400 uppercase">Tipe</div><div class="text-xs font-black text-slate-800">{{ $detailItem->tipe->nama }}</div></div>
                            <div><div class="text-[9px] font-bold text-slate-400 uppercase">Kategori</div><div class="text-xs font-black text-slate-800">{{ $detailItem->tipe->kategori->nama ?? '-' }}</div></div>
                            <div class="col-span-2 pt-2 border-t mt-2"><div class="text-[9px] font-bold text-slate-400 uppercase">Detail Spesifikasi / Ciri Fisik</div><div class="text-xs font-black text-slate-800">{{ $detailItem->keterangan }}</div></div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <h4 class="text-xs font-black text-rose-600 uppercase tracking-widest flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-rose-600"></span> Histori Mutasi</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-2">
                            @forelse($detailItem->mutasiBarangs as $m)
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center">
                                    <div><div class="text-[11px] font-black text-slate-800">{{ $m->alasan_mutasi }}</div><div class="text-[10px] text-slate-400 font-mono mt-1">{{ \Carbon\Carbon::parse($m->tanggal_mutasi)->format('d/m/Y | H:i') }}</div></div>
                                    <span class="text-[9px] font-black text-blue-500 uppercase">Logged</span>
                                </div>
                            @empty <div class="text-xs text-slate-400 italic text-center py-4">Belum ada riwayat.</div> @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-slate-50 border-t flex justify-end">
                <button wire:click="$set('isDetailModalOpen', false)" class="px-8 py-3 bg-slate-900 text-white font-black text-xs uppercase tracking-widest rounded-xl hover:bg-slate-700 transition-all shadow-lg">Tutup Panel Detail</button>
            </div>
        </div>
    </div>
    @endif

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-4xl flex flex-col max-h-[90vh] overflow-hidden">
            <div class="px-8 py-5 border-b bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-xl text-slate-800">Registrasi Barang Baru</h3>
                <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-8 overflow-y-auto">
                <form wire:submit="store" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {{-- KOLOM KIRI --}}
                    <div class="space-y-6">
                        <!-- <div class="p-4 bg-blue-50 rounded-2xl border border-blue-200">
                            <label class="block text-xs font-bold text-blue-700 mb-2 uppercase">QR Code Value</label>
                            <div class="text-[10px] font-mono font-bold text-blue-900 break-all">{{ route('scan.barang', $kode_inventaris) }}</div>
                        </div> -->

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Sumber Data --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Sumber Data <span class="text-rose-500">*</span></label>
                                <select wire:model.live="source_type" class="w-full rounded-2xl border-slate-200 text-sm focus:ring-blue-500">
                                    <option value="">Pilih Sumber...</option>
                                    <option value="App\Models\SapAsset">SAP Asset</option>
                                    <option value="App\Models\SapKcl">SAP KCL</option>
                                </select>
                                @error('source_type') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            {{-- Item SAP dengan Pencarian --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Item SAP <span class="text-rose-500">*</span></label>
                                <div x-data="{ open: false, search: '' }" class="relative">
                                    <button type="button" @click="open = !open" :disabled="!$wire.source_type" class="w-full bg-white border border-slate-200 rounded-2xl px-4 py-2.5 text-sm text-left flex justify-between items-center disabled:bg-slate-50">
                                        <span class="truncate">
                                            @if($source_id)
                                                @php $sel = ($source_type === 'App\Models\SapAsset') ? collect($assets)->firstWhere('id', $source_id) : collect($kcls)->firstWhere('id', $source_id); @endphp
                                                {{ $sel->asset_name ?? $sel->material_description ?? 'Item Terpilih' }}
                                            @else Cari Item SAP... @endif
                                        </span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl p-2">
                                        <input x-model="search" type="text" class="w-full rounded-xl border-slate-200 text-xs mb-2" placeholder="Cari No/Nama...">
                                        <div class="max-h-48 overflow-y-auto">
                                            @if($source_type === 'App\Models\SapAsset')
                                                @foreach($assets as $a)
                                                <div x-show="'{{ strtolower($a->asset_number . ' ' . $a->asset_name) }}'.includes(search.toLowerCase())" 
                                                    @click="$wire.set('source_id', {{ $a->id }}); open = false" class="px-3 py-2 hover:bg-blue-50 rounded-xl cursor-pointer text-xs">
                                                    <b>{{ $a->asset_number }}</b><br>{{ $a->asset_name }}
                                                </div>
                                                @endforeach
                                            @else
                                                @foreach($kcls as $k)
                                                <div x-show="'{{ strtolower($k->material . ' ' . $k->material_description) }}'.includes(search.toLowerCase())" 
                                                    @click="$wire.set('source_id', {{ $k->id }}); open = false" class="px-3 py-2 hover:bg-blue-50 rounded-xl cursor-pointer text-xs">
                                                    <b>{{ $k->material }}</b><br>{{ $k->material_description }}
                                                </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @error('source_id') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Kapasitas Display --}}
                        @if($source_type && $source_id)
                        <div class="p-4 rounded-2xl bg-slate-50 border flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-500 uppercase">Kapasitas SAP Terdaftar:</span>
                            <span class="text-lg font-black {{ $registeredQty >= $totalQty ? 'text-rose-600' : 'text-blue-600' }}">{{ $registeredQty }} / {{ $totalQty }}</span>
                        </div>
                        @endif

                        {{-- Tipe & Brand dengan Pencarian --}}
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Dropdown Tipe --}}
                            <div x-data="{ open: false, search: '' }">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Tipe <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <button type="button" @click="open = !open" class="w-full bg-white border border-slate-200 rounded-2xl px-4 py-2.5 text-sm text-left flex justify-between items-center">
                                        <span class="truncate">@if($tipe_id) {{ collect($tipes)->firstWhere('id', $tipe_id)->nama }} @else Pilih Tipe @endif</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl p-2">
                                        <input x-model="search" type="text" class="w-full rounded-xl border-slate-200 text-xs mb-2" placeholder="Cari tipe...">
                                        <div class="max-h-40 overflow-y-auto">
                                            @foreach($tipes as $t)
                                            <div x-show="'{{ strtolower($t->nama) }}'.includes(search.toLowerCase())" @click="$wire.set('tipe_id', {{ $t->id }}); open = false" class="px-3 py-2 hover:bg-blue-50 rounded-xl cursor-pointer text-xs">{{ $t->nama }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @error('tipe_id') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            {{-- Dropdown Brand --}}
                            <div x-data="{ open: false, search: '' }">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Brand <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <button type="button" @click="open = !open" class="w-full bg-white border border-slate-200 rounded-2xl px-4 py-2.5 text-sm text-left flex justify-between items-center">
                                        <span class="truncate">@if($brand_id) {{ collect($brands)->firstWhere('id', $brand_id)->nama }} @else Pilih Brand @endif</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl p-2">
                                        <input x-model="search" type="text" class="w-full rounded-xl border-slate-200 text-xs mb-2" placeholder="Cari brand...">
                                        <div class="max-h-40 overflow-y-auto">
                                            @foreach($brands as $b)
                                            <div x-show="'{{ strtolower($b->nama) }}'.includes(search.toLowerCase())" @click="$wire.set('brand_id', {{ $b->id }}); open = false" class="px-3 py-2 hover:bg-blue-50 rounded-xl cursor-pointer text-xs">{{ $b->nama }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @error('brand_id') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Keterangan Fisik (Di bawah Tipe & Brand) --}}
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Detail Spesifikasi / Ciri Fisik <span class="text-rose-500">*</span></label>
                            <textarea wire:model="keterangan" rows="3" class="w-full rounded-2xl border-slate-200 text-sm focus:ring-blue-500" placeholder="Detail Spesifikasi / Ciri Fisik..."></textarea>
                            @error('keterangan') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- KOLOM KANAN --}}
                    <div class="space-y-6 text-center">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 text-left">Upload Foto Fisik <span class="text-rose-500">*</span></label>
                            <div class="w-full h-64 border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50 flex items-center justify-center overflow-hidden relative group transition-all hover:bg-slate-100">
                                @if($foto_barang) 
                                    <img src="{{ $foto_barang->temporaryUrl() }}" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all">
                                        <input type="file" wire:model="foto_barang" class="absolute inset-0 opacity-0 cursor-pointer">
                                        <span class="text-white text-xs font-bold">Ganti Foto</span>
                                    </div>
                                @else 
                                    <input type="file" wire:model="foto_barang" class="absolute inset-0 opacity-0 cursor-pointer"> 
                                    <div class="p-4">
                                        <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"></path></svg>
                                        <p class="text-xs text-slate-400">Klik atau tarik foto ke sini</p>
                                        <p class="text-[10px] text-slate-300 mt-1 italic">Format: JPG, PNG (Maks 2MB)</p>
                                    </div>
                                @endif
                            </div>
                            @error('foto_barang') <span class="text-xs text-rose-500 font-bold mt-1 block text-left">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95 disabled:bg-slate-300 disabled:shadow-none"
                            {{ ($source_type && $source_id && $registeredQty >= $totalQty) ? 'disabled' : '' }}>
                            {{ ($source_type && $source_id && $registeredQty >= $totalQty) ? 'Kuota SAP Penuh' : 'Selesaikan Registrasi' }}
                        </button>
                        <p class="text-[10px] text-slate-400 italic">Pastikan seluruh data bertanda (*) telah terisi.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($isPhotoModalOpen)
    <div class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" @click.self="$wire.set('isPhotoModalOpen', false)">
        <button wire:click="$set('isPhotoModalOpen', false)" class="absolute top-6 right-6 text-white/50 hover:text-white transition-colors bg-white/10 p-2 rounded-full"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        @if($selectedPhotoUrl) <img src="{{ $selectedPhotoUrl }}" class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl"> @endif
    </div>
    @endif

    @if($isQrModalOpen && $selectedItem)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl p-8 max-w-sm w-full text-center relative">
            <button wire:click="$set('isQrModalOpen', false)" class="absolute top-4 right-4 text-slate-400 hover:text-rose-500 bg-slate-50 p-1.5 rounded-full"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            <h3 class="font-bold text-slate-800 text-lg mb-1">QR Code Scannable</h3>
            <p class="text-xs text-slate-500 mb-6 font-medium">{{ $selectedItem->sourceable->asset_name ?? $selectedItem->sourceable->material_description ?? 'Data' }}</p>
            <div class="bg-white p-4 border-4 border-slate-100 rounded-3xl inline-block mb-4 shadow-sm">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode(route('scan.barang', $selectedItem->kode_inventaris)) }}" class="w-48 h-48">
            </div>
            <div class="bg-blue-50 text-blue-700 py-2.5 px-4 rounded-xl font-mono text-[11px] font-bold break-all mb-4 border border-blue-100">{{ $selectedItem->kode_inventaris }}</div>
        </div>
    </div>
    @endif
</div>