<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Lantai;
use App\Models\Gedung;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $filterGedung = '';
    public $perPage = 10;

    public $isModalOpen = false;
    public $isEditMode = false;
    public $selectedId = null;

    // Field Database
    public $gedung_id = '';
    public $nama = '';
    public $keterangan = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterGedung' => ['except' => ''],
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterGedung() { $this->resetPage(); }

    public function openModal()
    {
        $this->reset(['selectedId', 'gedung_id', 'nama', 'keterangan', 'isEditMode']);
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $lantai = Lantai::findOrFail($id);
        $this->selectedId = $id;
        $this->gedung_id = $lantai->gedung_id;
        $this->nama = $lantai->nama;
        $this->keterangan = $lantai->keterangan;
        
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'gedung_id' => 'required|exists:gedungs,id',
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ], [
            'gedung_id.required' => 'Silakan pilih gedung terlebih dahulu.',
            'nama.required' => 'Nama tingkat/lantai wajib diisi.',
        ]);

        Lantai::create([
            'gedung_id' => $this->gedung_id,
            'nama' => $this->nama,
            'keterangan' => $this->keterangan,
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Lantai baru berhasil ditambahkan.');
    }

    public function update()
    {
        $this->validate([
            'gedung_id' => 'required|exists:gedungs,id',
            'nama' => 'required|string|max:255',
        ]);

        Lantai::findOrFail($this->selectedId)->update([
            'gedung_id' => $this->gedung_id,
            'nama' => $this->nama,
            'keterangan' => $this->keterangan,
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Data Lantai berhasil diperbarui.');
    }

    public function delete($id)
    {
        $lantai = Lantai::findOrFail($id);
        
        // Pengecekan sebelum dihapus (Jika ada ruangan)
        if (\App\Models\Ruangan::where('lantai_id', $id)->exists()) {
            session()->flash('error', "Gagal dihapus: Lantai '{$lantai->nama}' masih memiliki data Ruangan aktif. Hapus ruangannya terlebih dahulu.");
            return;
        }

        $lantai->delete();
        session()->flash('success', 'Lantai berhasil dihapus.');
    }

    public function with(): array
    {
        $query = Lantai::with(['gedung.cabang']); // Mengambil relasi berantai (Lantai -> Gedung -> Cabang)

        if ($this->search) {
            $query->where('nama', 'like', "%{$this->search}%")
                  ->orWhere('keterangan', 'like', "%{$this->search}%");
        }

        if ($this->filterGedung) {
            $query->where('gedung_id', $this->filterGedung);
        }

        return [
            'items' => $query->latest()->paginate($this->perPage),
            // Load semua gedung beserta nama cabangnya untuk Dropdown
            'gedungs' => Gedung::with('cabang')->orderBy('nama')->get(), 
        ];
    }
}; ?>

<div class="space-y-4">
    @if(session('success'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl text-sm font-bold flex items-center gap-2 border border-emerald-100">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="p-4 bg-rose-50 text-rose-700 rounded-2xl text-sm font-bold flex items-center gap-2 border border-rose-100">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-72">
                <input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500" placeholder="Cari Lantai...">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            
            <select wire:model.live="filterGedung" class="bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Gedung...</option>
                @foreach($gedungs as $gedung)
                    <option value="{{ $gedung->id }}">{{ $gedung->nama }} ({{ optional($gedung->cabang)->nama }})</option>
                @endforeach
            </select>
        </div>

        <button wire:click="openModal" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg transition-all flex items-center justify-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Lantai
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Tingkat / Lantai</th>
                        <th class="px-6 py-4">Induk Gedung & Cabang</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $item->nama }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ optional($item->gedung)->nama ?? 'Gedung Dihapus' }}</div>
                                <div class="text-[10px] text-slate-500 uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                    {{ optional(optional($item->gedung)->cabang)->nama ?? '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-500">{{ $item->keterangan ?: '-' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    <button wire:click="edit({{ $item->id }})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                    <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus Lantai ini?" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium">Belum ada data lantai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 bg-slate-50 border-t border-slate-100">{{ $items->links() }}</div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md overflow-visible flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between bg-slate-50 items-center rounded-t-[2rem]">
                <h3 class="font-bold text-xl text-slate-800">{{ $isEditMode ? 'Edit Lantai' : 'Tambah Lantai Baru' }}</h3>
                <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-rose-500 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-visible">
                <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}" class="space-y-5">
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Gedung Induk <span class="text-rose-500">*</span></label>
                        <div x-data="{ 
                                open: false, 
                                search: '',
                                get selectedName() {
                                    let items = @js($gedungs);
                                    let found = items.find(g => g.id == $wire.gedung_id);
                                    if(found) return found.nama + ' — ' + (found.cabang ? found.cabang.nama : '');
                                    return 'Cari Gedung...';
                                }
                             }" 
                             @click.away="open = false" 
                             class="relative">
                             
                            <button type="button" @click="open = !open" class="w-full text-left bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 flex justify-between items-center shadow-sm">
                                <span x-text="selectedName" class="truncate" :class="$wire.gedung_id ? 'text-slate-800 font-bold' : 'text-slate-400'"></span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            
                            <div x-show="open" style="display:none;" class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden">
                                <div class="p-2 border-b border-slate-100">
                                    <input x-model="search" type="text" class="w-full rounded-lg text-sm border-slate-200 focus:ring-blue-500" placeholder="Ketik nama gedung atau cabang...">
                                </div>
                                <ul class="max-h-56 overflow-y-auto p-1 bg-white">
                                    @foreach($gedungs as $g)
                                        <li x-show="'{{ strtolower($g->nama . ' ' . optional($g->cabang)->nama) }}'.includes(search.toLowerCase())" 
                                            @click="$wire.gedung_id = {{ $g->id }}; open = false" 
                                            class="px-4 py-2.5 text-sm hover:bg-blue-50 cursor-pointer rounded-lg border-b border-slate-50 last:border-0 transition-colors">
                                            <div class="font-bold text-slate-800">{{ $g->nama }}</div>
                                            <div class="text-[10px] text-slate-500 uppercase flex items-center gap-1 mt-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                {{ optional($g->cabang)->nama ?? 'Cabang Tidak Diketahui' }}
                                            </div>
                                        </li>
                                    @endforeach
                                    <li x-show="!(@js($gedungs).some(g => (g.nama + ' ' + (g.cabang ? g.cabang.nama : '')).toLowerCase().includes(search.toLowerCase())))" class="px-4 py-3 text-xs text-center text-slate-400">Tidak ada gedung yang cocok.</li>
                                </ul>
                            </div>
                        </div>
                        @error('gedung_id') <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nama Lantai / Tingkat <span class="text-rose-500">*</span></label>
                        <input wire:model="nama" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 shadow-sm" placeholder="Misal: Lantai 1, Rooftop, Basement 2">
                        @error('nama') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Keterangan (Opsional)</label>
                        <textarea wire:model="keterangan" rows="2" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 shadow-sm" placeholder="Misal: Area parkir dan kantin..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-bold text-slate-600 transition-colors">Batal</button>
                        <button type="submit" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition-all">{{ $isEditMode ? 'Simpan Perubahan' : 'Tambahkan Lantai' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>