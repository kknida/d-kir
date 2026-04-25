<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use App\Models\Ruangan;
use App\Models\Lantai;
use App\Models\PenanggungJawab; // Import Model PJ

new class extends Component {
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    public $isModalOpen = false, $isEditMode = false, $isQrModalOpen = false;
    public $selectedId = null, $selectedItem = null;

    // Field Database
    public $lantai_id = '', $nama = '', $kode_ruangan = '', $penanggung_jawab_id = '';

    public function updatedSearch() { $this->resetPage(); }

    public function openModal()
    {
        $this->reset(['selectedId', 'lantai_id', 'nama', 'penanggung_jawab_id', 'isEditMode']);
        $this->kode_ruangan = 'RM' . date('ymd') . strtoupper(Str::random(4));
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $this->selectedId = $id;
        $this->lantai_id = $ruangan->lantai_id;
        $this->nama = $ruangan->nama;
        $this->kode_ruangan = $ruangan->kode_ruangan;
        $this->penanggung_jawab_id = $ruangan->penanggung_jawab_id;
        
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function showQr($id)
    {
        $this->selectedItem = Ruangan::with(['lantai.gedung.cabang', 'penanggungJawab'])->findOrFail($id);
        $this->isQrModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'lantai_id' => 'required|exists:lantais,id',
            'nama' => 'required|string|max:255',
            'kode_ruangan' => 'required|unique:ruangans,kode_ruangan',
            'penanggung_jawab_id' => 'nullable|exists:penanggung_jawabs,id',
        ]);

        Ruangan::create([
            'lantai_id' => $this->lantai_id,
            'nama' => $this->nama,
            'kode_ruangan' => $this->kode_ruangan,
            'penanggung_jawab_id' => $this->penanggung_jawab_id,
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Ruangan berhasil diregistrasi dengan Penanggung Jawab.');
    }

    public function update()
    {
        $this->validate([
            'lantai_id' => 'required|exists:lantais,id',
            'nama' => 'required|string|max:255',
            'penanggung_jawab_id' => 'nullable|exists:penanggung_jawabs,id',
        ]);

        Ruangan::findOrFail($this->selectedId)->update([
            'lantai_id' => $this->lantai_id,
            'nama' => $this->nama,
            'penanggung_jawab_id' => $this->penanggung_jawab_id,
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Data Ruangan berhasil diperbarui.');
    }

    public function delete($id)
    {
        Ruangan::findOrFail($id)->delete();
        session()->flash('success', 'Ruangan berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'items' => Ruangan::with(['lantai.gedung.cabang', 'penanggungJawab'])
                ->where('nama', 'like', "%{$this->search}%")
                ->orWhere('kode_ruangan', 'like', "%{$this->search}%")
                ->latest()->paginate($this->perPage),
            'lantais' => Lantai::with('gedung.cabang')->orderBy('nama')->get(),
            'pjs' => PenanggungJawab::orderBy('nama')->get(), // Ambil data PJ untuk dropdown
        ];
    }
}; ?>

<div class="space-y-4">
    @if(session('success'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl text-sm font-bold border border-emerald-100 flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="relative w-full sm:w-72">
            <input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500" placeholder="Cari Nama/Kode Ruang...">
            <div class="absolute left-3 top-2.5 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        <button wire:click="openModal" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg flex items-center gap-2 text-sm transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Registrasi Ruangan
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Nama Ruangan</th>
                        <th class="px-6 py-4">Penanggung Jawab (PIC)</th>
                        <th class="px-6 py-4">Hierarki Lokasi</th>
                        <th class="px-6 py-4 text-center w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800">{{ $item->nama }}</div>
                                <div class="text-[10px] font-mono text-indigo-500 mt-1 uppercase tracking-tighter">{{ $item->kode_ruangan }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($item->penanggungJawab)
                                    <div class="font-bold text-slate-700 text-xs">{{ $item->penanggungJawab->nama }}</div>
                                    <div class="text-[10px] text-slate-500 italic">NIP: {{ $item->penanggungJawab->nip }}</div>
                                @else
                                    <span class="text-rose-400 text-xs italic">Belum ada PIC</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-slate-700 font-bold">{{ optional($item->lantai)->nama }}</div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-widest">{{ optional(optional($item->lantai)->gedung)->nama }} — {{ optional(optional(optional($item->lantai)->gedung)->cabang)->nama }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('ruangan.aset', $item->id) }}" class="p-1.5 text-blue-600 hover:bg-blue-800 hover:text-white rounded-lg transition-colors flex items-center gap-1 text-[10px] font-bold uppercase" title="Kelola Aset Ruangan">
                                        Buka KIR
                                    </a>
                                    <button wire:click="showQr({{ $item->id }})" class="p-1.5 text-slate-600 hover:bg-slate-800 hover:text-white rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg></button>
                                    <button wire:click="edit({{ $item->id }})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                    <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus Ruangan ini?" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium">Belum ada data ruangan.</td></tr>
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
                <h3 class="font-bold text-xl text-slate-800">{{ $isEditMode ? 'Edit Ruangan' : 'Registrasi Ruangan' }}</h3>
                <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-rose-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-visible">
                <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Kode Ruangan</label>
                        <input wire:model="kode_ruangan" type="text" readonly class="w-full rounded-xl border-slate-200 bg-slate-100 font-mono text-sm font-bold tracking-widest text-center cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Ruangan <span class="text-rose-500">*</span></label>
                        <input wire:model="nama" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm" placeholder="Misal: Ruang Server">
                        @error('nama') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Pilih Lantai <span class="text-rose-500">*</span></label>
                        <select wire:model="lantai_id" class="w-full rounded-xl border-slate-200 text-sm">
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach($lantais as $l)
                                <option value="{{ $l->id }}">{{ $l->nama }} ({{ optional($l->gedung)->nama }})</option>
                            @endforeach
                        </select>
                        @error('lantai_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Penanggung Jawab (PIC)</label>
                        <div x-data="{ open: false, search: '' }" @click.away="open = false" class="relative">
                            <button type="button" @click="open = !open" class="w-full text-left bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm flex justify-between items-center">
                                <span x-text="@js($pjs).find(p => p.id == $wire.penanggung_jawab_id)?.nama || 'Pilih PIC Ruangan...'" class="truncate" :class="$wire.penanggung_jawab_id ? 'text-slate-800 font-bold' : 'text-slate-400'"></span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div x-show="open" style="display:none;" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden">
                                <div class="p-2 border-b border-slate-100"><input x-model="search" type="text" class="w-full rounded-lg text-xs border-slate-200" placeholder="Cari nama/NIP..."></div>
                                <ul class="max-h-48 overflow-y-auto p-1 bg-white">
                                    <li @click="$wire.penanggung_jawab_id = ''; open = false" class="px-3 py-2 text-xs text-rose-500 hover:bg-rose-50 cursor-pointer rounded-lg italic">-- Tanpa Penanggung Jawab --</li>
                                    @foreach($pjs as $p)
                                        <li x-show="'{{ strtolower($p->nama . ' ' . $p->nip) }}'.includes(search.toLowerCase())" @click="$wire.penanggung_jawab_id = {{ $p->id }}; open = false" class="px-3 py-2 text-xs hover:bg-blue-50 cursor-pointer rounded-lg border-b border-slate-50">
                                            <div class="font-bold text-slate-800">{{ $p->nama }}</div>
                                            <div class="text-[9px] text-slate-500 font-mono">NIP: {{ $p->nip }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @error('penanggung_jawab_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-bold text-slate-600">Batal</button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition-all">Simpan Ruangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($isQrModalOpen && $selectedItem)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl p-8 max-w-sm w-full text-center relative border-4 border-indigo-500/20">
            <button wire:click="$set('isQrModalOpen', false)" class="absolute top-4 right-4 text-slate-400 hover:text-rose-500 bg-slate-50 p-1.5 rounded-full"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            <h3 class="font-black text-indigo-900 text-xl mb-1">KIR Lokasi</h3>
            <p class="text-sm font-bold text-slate-800 mb-4">{{ $selectedItem->nama }}</p>
            <div class="bg-white p-4 border-4 border-slate-100 rounded-3xl inline-block mb-4 shadow-sm"><img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode(url('/scan-ruangan/' . $selectedItem->kode_ruangan)) }}" class="w-40 h-40"></div>
            <div class="bg-indigo-50 text-indigo-700 py-2 px-4 rounded-xl font-mono text-xs font-bold break-all mb-4 border border-indigo-100 tracking-widest">{{ $selectedItem->kode_ruangan }}</div>
            <div class="text-[10px] text-left text-slate-500 uppercase flex flex-col gap-1.5 bg-slate-50 p-3 rounded-xl border border-slate-100 font-bold">
                <div class="flex justify-between border-b border-slate-200 pb-1"><span>Lantai:</span> <span class="text-slate-800">{{ optional($selectedItem->lantai)->nama }}</span></div>
                <div class="flex justify-between border-b border-slate-200 pb-1"><span>Cabang:</span> <span class="text-slate-800">{{ optional(optional(optional($selectedItem->lantai)->gedung)->cabang)->nama }}</span></div>
                <div class="flex justify-between"><span>PIC:</span> <span class="text-indigo-600">{{ $selectedItem->penanggungJawab ? $selectedItem->penanggungJawab->nama : 'Belum Ditentukan' }}</span></div>
            </div>
        </div>
    </div>
    @endif
</div>