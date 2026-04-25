<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\PenanggungJawab;
use App\Models\Jabatan;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $isModalOpen = false, $isEditMode = false, $selectedId = null;
    
    public $jabatan_id = '', $nama = '', $nip = '', $kontak = '', $keterangan = '';

    public function updatedSearch() { $this->resetPage(); }

    public function openModal() {
        $this->reset(['selectedId', 'jabatan_id', 'nama', 'nip', 'kontak', 'keterangan', 'isEditMode']);
        $this->isModalOpen = true;
    }

    public function edit($id) {
        $pj = PenanggungJawab::findOrFail($id);
        $this->selectedId = $id;
        $this->jabatan_id = $pj->jabatan_id;
        $this->nama = $pj->nama;
        $this->nip = $pj->nip;
        $this->kontak = $pj->kontak;
        $this->keterangan = $pj->keterangan;
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function store() {
        $this->validate([
            'jabatan_id' => 'required|exists:jabatans,id',
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|unique:penanggung_jawabs,nip',
        ], [
            'jabatan_id.required' => 'Pilih jabatan terlebih dahulu.',
            'nip.unique' => 'NIP sudah terdaftar di sistem.'
        ]);

        PenanggungJawab::create([
            'jabatan_id' => $this->jabatan_id, 'nama' => $this->nama,
            'nip' => $this->nip, 'kontak' => $this->kontak, 'keterangan' => $this->keterangan
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Penanggung Jawab berhasil diregistrasi.');
    }

    public function update() {
        $this->validate([
            'jabatan_id' => 'required|exists:jabatans,id',
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|unique:penanggung_jawabs,nip,' . $this->selectedId,
        ]);

        PenanggungJawab::findOrFail($this->selectedId)->update([
            'jabatan_id' => $this->jabatan_id, 'nama' => $this->nama,
            'nip' => $this->nip, 'kontak' => $this->kontak, 'keterangan' => $this->keterangan
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Data Penanggung Jawab berhasil diperbarui.');
    }

    public function delete($id) {
        // Nanti bisa ditambahkan validasi jika PJ masih nyangkut di tabel Ruangan
        PenanggungJawab::findOrFail($id)->delete();
        session()->flash('success', 'Penanggung Jawab berhasil dihapus.');
    }

    public function with(): array {
        return [
            'items' => PenanggungJawab::with('jabatan')
                ->where('nama', 'like', "%{$this->search}%")
                ->orWhere('nip', 'like', "%{$this->search}%")
                ->latest()->paginate($this->perPage),
            'jabatans' => Jabatan::orderBy('nama')->get(),
        ];
    }
}; ?>

<div class="space-y-4">
    @if(session('success')) <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl text-sm font-bold border border-emerald-100">{{ session('success') }}</div> @endif

    <div class="flex justify-between gap-4">
        <input wire:model.live.debounce.300ms="search" type="text" class="w-full sm:w-80 rounded-xl border-slate-200 text-sm focus:ring-blue-500 bg-slate-50" placeholder="Cari Nama / NIP...">
        <button wire:click="openModal" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-lg shrink-0">Registrasi PJ</button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                <tr>
                    <th class="px-6 py-4">Data Pegawai</th>
                    <th class="px-6 py-4">Jabatan</th>
                    <th class="px-6 py-4">Kontak / Keterangan</th>
                    <th class="px-6 py-4 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800">{{ $item->nama }}</div>
                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">NIP: {{ $item->nip }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold">{{ optional($item->jabatan)->nama ?? 'Jabatan Dihapus' }}</span>
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs">
                            <div class="font-bold">{{ $item->kontak ?: 'Tidak ada kontak' }}</div>
                            <div class="text-[10px]">{{ $item->keterangan }}</div>
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <button wire:click="edit({{ $item->id }})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                            <button wire:click="delete({{ $item->id }})" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400">Belum ada penanggung jawab.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3 bg-slate-50 border-t border-slate-100">{{ $items->links() }}</div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl overflow-visible">
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between bg-slate-50 rounded-t-[2rem]">
                <h3 class="font-bold text-lg text-slate-800">{{ $isEditMode ? 'Edit Penanggung Jawab' : 'Registrasi Penanggung Jawab' }}</h3>
                <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-rose-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">NIP Pegawai <span class="text-rose-500">*</span></label>
                        <input wire:model="nip" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm font-mono" placeholder="Masukkan NIP">
                        @error('nip') <span class="text-xs text-rose-500 font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input wire:model="nama" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm" placeholder="Nama Pegawai">
                        @error('nama') <span class="text-xs text-rose-500 font-bold">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Jabatan <span class="text-rose-500">*</span></label>
                        <select wire:model="jabatan_id" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                            <option value="">Pilih Jabatan...</option>
                            @foreach($jabatans as $jabatan)
                                <option value="{{ $jabatan->id }}">{{ $jabatan->nama }}</option>
                            @endforeach
                        </select>
                        @error('jabatan_id') <span class="text-xs text-rose-500 font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Kontak (Opsional)</label>
                        <input wire:model="kontak" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm" placeholder="No HP / Ext. Kantor">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Keterangan</label>
                    <textarea wire:model="keterangan" rows="2" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" wire:click="$set('isModalOpen', false)" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-bold text-slate-600">Batal</button>
                    <button type="submit" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg">{{ $isEditMode ? 'Simpan' : 'Tambahkan' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>