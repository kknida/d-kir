<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Kategori;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    // State Modal
    public $isModalOpen = false;
    public $isEditMode = false;
    public $selectedId = null;

    // Field Database
    public $nama = '';
    public $keterangan = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatedSearch() { $this->resetPage(); }
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
        $this->reset(['selectedId', 'nama', 'keterangan']);
        $this->isEditMode = false;
    }

    public function store()
    {
        $this->validate([
            'nama' => 'required|unique:kategoris,nama',
            'keterangan' => 'nullable|string|max:255',
        ], [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.unique' => 'Nama kategori ini sudah ada.',
        ]);

        Kategori::create([
            'nama' => $this->nama,
            'keterangan' => $this->keterangan,
        ]);

        $this->closeModal();
        session()->flash('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $kategori = Kategori::findOrFail($id);
        $this->selectedId = $id;
        $this->nama = $kategori->nama;
        $this->keterangan = $kategori->keterangan;
        
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function update()
    {
        $this->validate([
            'nama' => 'required|unique:kategoris,nama,' . $this->selectedId,
            'keterangan' => 'nullable|string|max:255',
        ]);

        Kategori::findOrFail($this->selectedId)->update([
            'nama' => $this->nama,
            'keterangan' => $this->keterangan,
        ]);

        $this->closeModal();
        session()->flash('success', 'Data kategori berhasil diperbarui.');
    }

    public function delete($id)
    {
        // 1. Cari kategori yang ingin dihapus
        $kategori = Kategori::findOrFail($id);

        // 2. Cek apakah ada Tipe yang menggunakan ID kategori ini
        $isUsedByTipe = \App\Models\Tipe::where('kategori_id', $id)->exists();

        if ($isUsedByTipe) {
            // Jika masih dipakai, batalkan penghapusan dan kirim pesan error
            session()->flash('error', "Kategori '{$kategori->nama}' tidak bisa dihapus karena masih digunakan oleh Master Tipe. Silakan hapus atau ubah tipe yang terkait terlebih dahulu.");
            return; 
        }

        // 3. Jika aman (tidak ada yang pakai), baru hapus
        $kategori->delete();
        session()->flash('success', 'Kategori berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'items' => Kategori::where('nama', 'like', "%{$this->search}%")
                ->orWhere('keterangan', 'like', "%{$this->search}%")
                ->latest()
                ->paginate($this->perPage),
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
    @if(session()->has('error'))
        <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl text-sm font-bold flex items-start gap-2"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="relative w-full sm:w-72">
            <input wire:model.live.debounce.300ms="search" type="text" 
                   class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500" 
                   placeholder="Cari kategori...">
            <div class="absolute left-3 top-2.5 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>

        <button wire:click="openModal" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl font-bold shadow-lg shadow-blue-100 transition-all flex items-center justify-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Kategori
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Nama Kategori</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3 font-bold text-slate-800">{{ $item->nama }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $item->keterangan ?: '-' }}</td>
                            <td class="px-6 py-3">
                                <div class="flex justify-center gap-2">
                                    <button wire:click="edit({{ $item->id }})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button wire:click="delete({{ $item->id }})" wire:confirm="Yakin ingin menghapus kategori '{{ $item->nama }}'?" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-slate-400">Belum ada data kategori.</td></tr>
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
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ $isEditMode ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
                <button wire:click="closeModal" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6">
                <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                        <input wire:model="nama" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500" placeholder="Contoh: Elektronik, Kendaraan">
                        @error('nama') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Keterangan (Opsional)</label>
                        <textarea wire:model="keterangan" rows="3" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500" placeholder="Deskripsi singkat..."></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg shadow-blue-100 transition-all">
                            {{ $isEditMode ? 'Simpan Perubahan' : 'Simpan Kategori' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>