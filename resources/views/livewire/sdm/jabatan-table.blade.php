<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Jabatan;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $isModalOpen = false, $isEditMode = false, $selectedId = null;
    public $nama = '', $keterangan = '';

    public function updatedSearch() { $this->resetPage(); }

    public function openModal() {
        $this->reset(['selectedId', 'nama', 'keterangan', 'isEditMode']);
        $this->isModalOpen = true;
    }

    public function edit($id) {
        $jabatan = Jabatan::findOrFail($id);
        $this->selectedId = $id;
        $this->nama = $jabatan->nama;
        $this->keterangan = $jabatan->keterangan;
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function store() {
        $this->validate(['nama' => 'required|string|max:255|unique:jabatans,nama']);
        Jabatan::create(['nama' => $this->nama, 'keterangan' => $this->keterangan]);
        $this->isModalOpen = false;
        session()->flash('success', 'Jabatan berhasil ditambahkan.');
    }

    public function update() {
        $this->validate(['nama' => 'required|string|max:255|unique:jabatans,nama,' . $this->selectedId]);
        Jabatan::findOrFail($this->selectedId)->update(['nama' => $this->nama, 'keterangan' => $this->keterangan]);
        $this->isModalOpen = false;
        session()->flash('success', 'Jabatan berhasil diperbarui.');
    }

    public function delete($id) {
        $jabatan = Jabatan::findOrFail($id);
        if (\App\Models\PenanggungJawab::where('jabatan_id', $id)->exists()) {
            session()->flash('error', 'Gagal: Jabatan masih digunakan oleh Penanggung Jawab aktif.');
            return;
        }
        $jabatan->delete();
        session()->flash('success', 'Jabatan berhasil dihapus.');
    }

    public function with(): array {
        return [
            'items' => Jabatan::where('nama', 'like', "%{$this->search}%")->latest()->paginate($this->perPage),
        ];
    }
}; ?>

<div class="space-y-4">
    @if(session('success')) <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl text-sm font-bold border border-emerald-100">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="p-4 bg-rose-50 text-rose-700 rounded-2xl text-sm font-bold border border-rose-100">{{ session('error') }}</div> @endif

    <div class="flex justify-between gap-4">
        <input wire:model.live.debounce.300ms="search" type="text" class="w-full sm:w-80 rounded-xl border-slate-200 text-sm focus:ring-blue-500 bg-slate-50" placeholder="Cari Jabatan...">
        <button wire:click="openModal" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-lg shrink-0">Tambah Jabatan</button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                <tr><th class="px-6 py-4">Nama Jabatan</th><th class="px-6 py-4">Keterangan</th><th class="px-6 py-4 text-center w-28">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-bold text-slate-800">{{ $item->nama }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $item->keterangan ?: '-' }}</td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <button wire:click="edit({{ $item->id }})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                            <button wire:click="delete({{ $item->id }})" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-8 text-center text-slate-400">Belum ada data jabatan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3 bg-slate-50 border-t border-slate-100">{{ $items->links() }}</div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ $isEditMode ? 'Edit Jabatan' : 'Tambah Jabatan' }}</h3>
                <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-rose-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nama Jabang <span class="text-rose-500">*</span></label>
                    <input wire:model="nama" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm">
                    @error('nama') <span class="text-xs text-rose-500 font-bold">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Keterangan</label>
                    <textarea wire:model="keterangan" rows="2" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" wire:click="$set('isModalOpen', false)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-bold text-slate-600">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg">{{ $isEditMode ? 'Simpan' : 'Tambahkan' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>