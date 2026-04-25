<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Tipe;
use App\Models\Kategori;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $filterKategori = '';

    // State Modal
    public $isModalOpen = false;
    public $isEditMode = false;
    public $selectedId = null;

    // Field Database
    public $kategori_id = '';
    public $nama = '';
    public $keterangan = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterKategori' => ['except' => ''],
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterKategori() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    private function resetForm()
    {
        $this->reset(['selectedId', 'kategori_id', 'nama', 'keterangan']);
        $this->isEditMode = false;
    }

    public function store()
    {
        $this->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'nama' => 'required',
            'keterangan' => 'nullable|string',
        ]);

        Tipe::create([
            'kategori_id' => $this->kategori_id,
            'nama' => $this->nama,
            'keterangan' => $this->keterangan,
        ]);

        $this->closeModal();
        session()->flash('success', 'Tipe barang berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $tipe = Tipe::findOrFail($id);
        $this->selectedId = $id;
        $this->kategori_id = $tipe->kategori_id;
        $this->nama = $tipe->nama;
        $this->keterangan = $tipe->keterangan;
        
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function update()
    {
        $this->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'nama' => 'required',
            'keterangan' => 'nullable|string',
        ]);

        Tipe::findOrFail($this->selectedId)->update([
            'kategori_id' => $this->kategori_id,
            'nama' => $this->nama,
            'keterangan' => $this->keterangan,
        ]);

        $this->closeModal();
        session()->flash('success', 'Data tipe berhasil diperbarui.');
    }

    public function delete($id)
    {
        Tipe::findOrFail($id)->delete();
        session()->flash('success', 'Tipe barang berhasil dihapus.');
    }

    public function with(): array
    {
        $query = Tipe::with('kategori');

        if ($this->search) {
            $query->where('nama', 'like', "%{$this->search}%");
        }

        if ($this->filterKategori) {
            $query->where('kategori_id', $this->filterKategori);
        }

        return [
            'items' => $query->latest()->paginate($this->perPage),
            'kategoris' => Kategori::orderBy('nama')->get(),
        ];
    }
}; ?>

<div class="space-y-4">
    @if(session()->has('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl text-sm font-bold flex items-center gap-2"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex flex-wrap gap-3 w-full md:w-auto">
            <div class="relative w-full sm:w-64">
                <input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500" placeholder="Cari tipe...">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            
            <select wire:model.live="filterKategori" class="bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 pr-8">
                <option value="">Semua Kategori</option>
                @foreach($kategoris as $kat)
                    <option value="{{ $kat->id }}">{{ $kat->nama }}</option>
                @endforeach
            </select>
        </div>

        <button wire:click="openModal" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl font-bold shadow-lg shadow-blue-100 transition-all flex items-center justify-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Tipe
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Nama Tipe</th>
                        <th class="px-6 py-4">Spesifikasi /  Detail Barang</th>
                        <th class="px-6 py-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3">
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold">{{ optional($item->kategori)->nama ?? 'Kategori Dihapus' }}</span>
                            </td>
                            <td class="px-6 py-3 font-bold text-slate-800">{{ $item->nama }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $item->keterangan ?: '-' }}</td>
                            <td class="px-6 py-3">
                                <div class="flex justify-center gap-2">
                                    <button wire:click="edit({{ $item->id }})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus tipe '{{ $item->nama }}'?" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400">Belum ada data tipe.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 bg-slate-50 border-t border-slate-100">
            {{ $items->links() }}
        </div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-visible flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ $isEditMode ? 'Edit Tipe' : 'Tambah Tipe Baru' }}</h3>
                <button wire:click="closeModal" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 overflow-visible">
                <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}" class="space-y-4">
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                        
                        <div x-data="{
                                open: false,
                                search: '',
                                get selectedName() {
                                    // Mencari nama kategori berdasarkan ID yang dipilih di Livewire
                                    let items = @js($kategoris);
                                    let found = items.find(k => k.id == $wire.kategori_id);
                                    return found ? found.nama : 'Ketik untuk mencari kategori...';
                                },
                                select(id) {
                                    $wire.kategori_id = id;
                                    this.open = false;
                                    this.search = '';
                                }
                             }"
                             @click.away="open = false"
                             class="relative w-full">
                             
                            <button type="button" 
                                    @click="open = !open" 
                                    class="w-full text-left bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 flex justify-between items-center shadow-sm">
                                <span x-text="selectedName" :class="$wire.kategori_id ? 'text-slate-800 font-bold' : 'text-slate-400'"></span>
                                <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="open" 
                                 x-transition.opacity.duration.200ms
                                 style="display: none;" 
                                 class="absolute z-[100] w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden flex flex-col">
                                
                                <div class="p-2 border-b border-slate-100 bg-slate-50">
                                    <div class="relative">
                                        <input x-model="search" x-ref="searchInput" type="text" class="w-full bg-white border-slate-200 rounded-lg text-sm pl-9 pr-3 py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Cari nama kategori...">
                                        <div class="absolute left-3 top-2.5 text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <ul class="overflow-y-auto max-h-48 p-1">
                                    @foreach($kategoris as $kat)
                                        <li x-show="search === '' || '{{ strtolower($kat->nama) }}'.includes(search.toLowerCase())"
                                            @click="select({{ $kat->id }})"
                                            class="px-4 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg cursor-pointer transition-colors flex items-center justify-between"
                                            :class="$wire.kategori_id == {{ $kat->id }} ? 'bg-blue-50 text-blue-700 font-bold' : ''">
                                            <span>{{ $kat->nama }}</span>
                                            <svg x-show="$wire.kategori_id == {{ $kat->id }}" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </li>
                                    @endforeach
                                    
                                    <li class="px-4 py-3 text-sm text-slate-400 text-center italic" 
                                        x-show="!(@js($kategoris).some(k => k.nama.toLowerCase().includes(search.toLowerCase())))">
                                        Kategori tidak ditemukan.
                                    </li>
                                </ul>
                            </div>
                        </div>

                        @error('kategori_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Tipe <span class="text-rose-500">*</span></label>
                        <input wire:model="nama" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 shadow-sm" placeholder="Contoh: Laptop, Meja, Printer">
                        @error('nama') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Spesifikasi /  Detail Barang</label>
                        <textarea wire:model="keterangan" rows="3" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 shadow-sm"></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 mt-4">
                        <button type="button" wire:click="closeModal" class="px-4 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg">
                            {{ $isEditMode ? 'Simpan Perubahan' : 'Simpan Tipe' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>