<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Barang;
use App\Models\Ruangan;
use App\Models\Kir;
use App\Models\MutasiBarang;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 10;
    public $isModalOpen = false, $isEditMode = false;
    
    public $selectedId = null;
    public $nama_barang = '', $merek = '', $tipe = '', $keterangan = '';
    public $foto_barang;
    
    // Untuk penempatan awal (KIR)
    public $ruangan_id = ''; 

    public function updatedSearch() { $this->resetPage(); }

    public function openModal() {
        $this->reset(['selectedId', 'nama_barang', 'merek', 'tipe', 'keterangan', 'foto_barang', 'ruangan_id', 'isEditMode']);
        $this->isModalOpen = true;
    }

    public function store() {
        $this->validate([
            'nama_barang' => 'required|string|max:255',
            'merek' => 'nullable|string|max:255',
            'ruangan_id' => 'required|exists:ruangans,id', // Wajib pilih ruangan awal
            'foto_barang' => 'nullable|image|max:2048',
        ]);

        DB::transaction(function () {
            // 1. Simpan Data Master Barang
            $path = $this->foto_barang ? $this->foto_barang->store('aset', 'public') : null;
            
            $barang = Barang::create([
                'nama_barang' => $this->nama_barang,
                'merek' => $this->merek,
                'tipe' => $this->tipe,
                'keterangan' => $this->keterangan,
                'foto_barang' => $path,
            ]);

            // 2. Daftarkan langsung ke tabel KIR (Penempatan Awal)
            Kir::create([
                'ruangan_id' => $this->ruangan_id,
                'barang_id' => $barang->id,
                'kondisi' => 'Baik', // Default barang baru
            ]);

            // 3. Catat di Log Mutasi Barang
            MutasiBarang::create([
                'barang_id' => $barang->id,
                'ruangan_asal_id' => $this->ruangan_id, // Asal dan Tujuan sama karena baru masuk
                'ruangan_tujuan_id' => $this->ruangan_id,
                'user_id' => auth()->id() ?? 1,
                'tanggal_mutasi' => now(),
                'alasan_mutasi' => 'Registrasi Awal Aset Baru',
            ]);
        });

        $this->isModalOpen = false;
        session()->flash('success', 'Aset baru berhasil didaftarkan dan ditempatkan ke ruangan.');
    }

    public function delete($id) {
        $barang = Barang::findOrFail($id);
        // Hapus file gambar jika ada
        if ($barang->foto_barang) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($barang->foto_barang);
        }
        $barang->delete(); // Pastikan di skema database Anda kirs memiliki onDelete('cascade')
        session()->flash('success', 'Data Aset berhasil dihapus sepenuhnya.');
    }

    public function with(): array {
        return [
            // Load barang beserta lokasi dan relasi polymorphic-nya
            'items' => Barang::with(['lokasiTerkini.ruangan', 'sourceable', 'tipe', 'brand'])
                ->where('kode_inventaris', 'like', "%{$this->search}%")
                ->orWhere('keterangan', 'like', "%{$this->search}%")
                ->latest()
                ->paginate($this->perPage),
            'ruangans' => Ruangan::with('lantai.gedung')->orderBy('nama')->get(),
        ];
    }
}; ?>

<div class="space-y-4">
    @if(session('success'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl text-sm font-bold border border-emerald-100 flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="relative w-full sm:w-80">
            <input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 shadow-sm" placeholder="Cari Kode Aset / Nama Barang...">
            <div class="absolute left-3 top-3 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        <button wire:click="openModal" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-200 flex items-center justify-center gap-2 text-sm transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Registrasi Aset Baru
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Data Aset</th>
                        <th class="px-6 py-4">Spesifikasi</th>
                        <th class="px-6 py-4">Lokasi Terkini (KIR)</th>
                        <th class="px-6 py-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 shrink-0 overflow-hidden">
                                        @if($item->foto_barang)
                                            <img src="{{ asset('storage/' . $item->foto_barang) }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-slate-300"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg></div>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-700"><span class="font-bold">Merek:</span> {{ $item->merek ?: '-' }}</div>
                                    <div class="text-xs text-slate-700 flex gap-2">
                                        <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px] font-bold uppercase">{{ optional($item->brand)->nama ?? '-' }}</span>
                                        <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px] font-bold uppercase">{{ optional($item->tipe)->nama ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-slate-700"><span class="font-bold">Merek:</span> {{ $item->merek ?: '-' }}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $item->keterangan }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($item->lokasiTerkini)
                                    <div class="font-bold text-slate-800 text-xs">{{ optional($item->lokasiTerkini->ruangan)->nama }}</div>
                                    
                                    @php
                                        $color = match($item->lokasiTerkini->kondisi) {
                                            'Baik' => 'text-emerald-600 bg-emerald-50 border-emerald-100',
                                            'Rusak Ringan' => 'text-amber-600 bg-amber-50 border-amber-100',
                                            'Rusak Berat' => 'text-rose-600 bg-rose-50 border-rose-100',
                                            default => 'text-slate-600 bg-slate-50 border-slate-100'
                                        };
                                    @endphp
                                    <div class="inline-block mt-1 px-2 py-0.5 rounded text-[9px] font-bold uppercase border {{ $color }}">
                                        Kondisi: {{ $item->lokasiTerkini->kondisi }}
                                    </div>
                                @else
                                    <span class="text-xs text-rose-400 italic font-medium">Belum Ditempatkan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 flex justify-center gap-2">
                                <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus aset ini beserta data KIR-nya?" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium">Belum ada data Master Aset.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 bg-slate-50 border-t border-slate-100">{{ $items->links() }}</div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl overflow-visible flex flex-col max-h-[90vh]">
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between bg-slate-50 items-center rounded-t-[2rem]">
                <h3 class="font-bold text-xl text-slate-800">Registrasi Aset Baru</h3>
                <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-rose-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <form wire:submit="store" class="space-y-6">
                    
                    <div class="bg-indigo-50 text-indigo-700 p-4 rounded-2xl border border-indigo-100 text-sm font-medium flex gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        Sistem akan otomatis meng-generate Kode Inventaris dan mencatat mutasi awal saat Anda menyimpan data ini.
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Nama Barang / Aset <span class="text-rose-500">*</span></label>
                            <input wire:model="nama_barang" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm" placeholder="Misal: AC Daikin 1 PK">
                            @error('nama_barang') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Merek / Brand</label>
                            <input wire:model="merek" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm" placeholder="Misal: Daikin, Samsung">
                        </div>
                    </div>

                    <div class="p-5 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50">
                        <label class="block text-sm font-black text-slate-800 mb-2">Penempatan Awal (Lokasi KIR) <span class="text-rose-500">*</span></label>
                        <p class="text-xs text-slate-500 mb-3">Tentukan di ruangan mana aset ini akan ditempatkan pertama kali.</p>
                        
                        <div x-data="{ open: false, search: '' }" @click.away="open = false" class="relative">
                            <button type="button" @click="open = !open" class="w-full text-left bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 flex justify-between items-center shadow-sm">
                                <span x-text="@js($ruangans).find(r => r.id == $wire.ruangan_id)?.nama || 'Pilih Ruangan Penempatan...'" class="truncate font-bold" :class="$wire.ruangan_id ? 'text-blue-600' : 'text-slate-400'"></span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div x-show="open" style="display:none;" class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden">
                                <div class="p-2 border-b border-slate-100"><input x-model="search" type="text" class="w-full rounded-lg text-sm border-slate-200" placeholder="Cari nama ruangan..."></div>
                                <ul class="max-h-48 overflow-y-auto p-1">
                                    @foreach($ruangans as $r)
                                        <li x-show="'{{ strtolower($r->nama) }}'.includes(search.toLowerCase())" @click="$wire.ruangan_id = {{ $r->id }}; open = false" class="px-4 py-2 text-sm hover:bg-blue-50 cursor-pointer rounded-lg border-b border-slate-50">
                                            <div class="font-bold text-slate-800">{{ $r->nama }}</div>
                                            <div class="text-[10px] text-slate-500 uppercase">{{ optional($r->lantai)->nama }} - {{ optional(optional($r->lantai)->gedung)->nama }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @error('ruangan_id') <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Upload Foto Aset Fisik (Opsional)</label>
                        <input type="file" wire:model="foto_barang" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-xl p-2">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Spesifikasi Detail / Keterangan</label>
                        <textarea wire:model="keterangan" rows="2" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm" placeholder="Catatan khusus mengenai aset ini..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-bold text-slate-600 transition-colors">Batal</button>
                        <button type="submit" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition-all">Simpan & Tempatkan Aset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>