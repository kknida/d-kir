<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Gedung;
use App\Models\Cabang;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $filterCabang = '';
    public $perPage = 10;

    public $isModalOpen = false;
    public $isEditMode = false;
    public $selectedId = null;

    // Field Database
    public $cabang_id = '';
    public $nama = '';
    public $alamat = '';
    public $koordinat = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCabang' => ['except' => ''],
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterCabang() { $this->resetPage(); }

    public function openModal()
    {
        $this->reset(['selectedId', 'cabang_id', 'nama', 'alamat', 'koordinat', 'isEditMode']);
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $gedung = Gedung::findOrFail($id);
        $this->selectedId = $id;
        $this->cabang_id = $gedung->cabang_id;
        $this->nama = $gedung->nama;
        $this->alamat = $gedung->alamat;
        $this->koordinat = $gedung->koordinat;
        
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'cabang_id' => 'required|exists:cabangs,id',
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'koordinat' => 'nullable|string',
        ], [
            'cabang_id.required' => 'Silakan pilih cabang terlebih dahulu.',
            'nama.required' => 'Nama gedung wajib diisi.',
        ]);

        Gedung::create([
            'cabang_id' => $this->cabang_id,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'koordinat' => $this->koordinat,
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Gedung baru berhasil ditambahkan.');
    }

    public function update()
    {
        $this->validate([
            'cabang_id' => 'required|exists:cabangs,id',
            'nama' => 'required|string|max:255',
        ]);

        Gedung::findOrFail($this->selectedId)->update([
            'cabang_id' => $this->cabang_id,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'koordinat' => $this->koordinat,
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Data Gedung berhasil diperbarui.');
    }

    public function delete($id)
    {
        $gedung = Gedung::findOrFail($id);
        
        // Pengecekan relasi ke tabel Lantai (Jika nanti sudah dibuat)
        if (\App\Models\Lantai::where('gedung_id', $id)->exists()) {
            session()->flash('error', "Gagal dihapus: Gedung '{$gedung->nama}' masih memiliki data Lantai aktif.");
            return;
        }

        $gedung->delete();
        session()->flash('success', 'Gedung berhasil dihapus.');
    }

    public function with(): array
    {
        $query = Gedung::with('cabang');

        if ($this->search) {
            $query->where('nama', 'like', "%{$this->search}%")
                  ->orWhere('alamat', 'like', "%{$this->search}%");
        }

        if ($this->filterCabang) {
            $query->where('cabang_id', $this->filterCabang);
        }

        return [
            'items' => $query->latest()->paginate($this->perPage),
            'cabangs' => Cabang::orderBy('nama')->get(),
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
                <input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500" placeholder="Cari Nama Gedung...">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            
            <select wire:model.live="filterCabang" class="bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Cabang...</option>
                @foreach($cabangs as $cabang)
                    <option value="{{ $cabang->id }}">{{ $cabang->nama }}</option>
                @endforeach
            </select>
        </div>

        <button wire:click="openModal" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg transition-all flex items-center justify-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Gedung
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Nama Gedung</th>
                        <th class="px-6 py-4">Induk Cabang</th>
                        <th class="px-6 py-4">Alamat & Koordinat</th>
                        <th class="px-6 py-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $item->nama }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold">{{ optional($item->cabang)->nama ?? 'Cabang Dihapus' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-slate-600 line-clamp-1 mb-1">{{ $item->alamat ?: '-' }}</p>
                                @if($item->koordinat)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ str_replace(' ', '', $item->koordinat) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded text-[10px] font-mono font-bold transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                        {{ $item->koordinat }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    <button wire:click="edit({{ $item->id }})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                    <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus Gedung ini?" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400">Belum ada data gedung.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 bg-slate-50 border-t border-slate-100">{{ $items->links() }}</div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl overflow-visible flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between bg-slate-50 items-center">
                <h3 class="font-bold text-xl text-slate-800">{{ $isEditMode ? 'Edit Gedung' : 'Tambah Gedung Baru' }}</h3>
                <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-rose-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-visible max-h-[85vh]">
                <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Cabang <span class="text-rose-500">*</span></label>
                            <div x-data="{ open: false, search: '' }" @click.away="open = false" class="relative">
                                <button type="button" @click="open = !open" class="w-full text-left bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 flex justify-between items-center shadow-sm">
                                    <span x-text="@js($cabangs).find(c => c.id == $wire.cabang_id)?.nama || 'Pilih Cabang...'" class="truncate" :class="$wire.cabang_id ? 'text-slate-800 font-bold' : 'text-slate-400'"></span>
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div x-show="open" style="display:none;" class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden">
                                    <div class="p-2 border-b border-slate-100"><input x-model="search" type="text" class="w-full rounded-lg text-sm border-slate-200" placeholder="Cari cabang..."></div>
                                    <ul class="max-h-48 overflow-y-auto p-1 bg-white">
                                        @foreach($cabangs as $c)
                                            <li x-show="'{{ strtolower($c->nama) }}'.includes(search.toLowerCase())" @click="$wire.cabang_id = {{ $c->id }}; open = false" class="px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer rounded-lg">{{ $c->nama }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            @error('cabang_id') <span class="text-xs text-rose-500 mt-1 block font-bold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Gedung <span class="text-rose-500">*</span></label>
                            <input wire:model="nama" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 shadow-sm" placeholder="Misal: Gedung Utama, Tower B">
                            @error('nama') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Alamat Lengkap</label>
                        <textarea wire:model="alamat" rows="2" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 shadow-sm" placeholder="Jalan, Nomor, Kelurahan..."></textarea>
                    </div>

                    <div x-data="{
                            showMap: false,
                            map: null,
                            marker: null,
                            isLoadingLocation: false,
                            searchQuery: '',
                            isSearching: false,
                            searchResults: [],
                            
                            async searchLocation() {
                                if (!this.searchQuery) return;
                                this.isSearching = true;
                                this.searchResults = [];
                                try {
                                    let response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&limit=5&countrycodes=id`);
                                    let data = await response.json();
                                    if(data.length > 0) { this.searchResults = data; } 
                                    else { alert('Lokasi tidak ditemukan.'); }
                                } catch (e) { alert('Terjadi kesalahan pencarian.'); }
                                this.isSearching = false;
                            },

                            selectLocation(lat, lon, displayName) {
                                let newLatLng = new L.LatLng(parseFloat(lat), parseFloat(lon));
                                this.map.setView(newLatLng, 16);
                                if(this.marker) this.marker.setLatLng(newLatLng);
                                $wire.set('koordinat', parseFloat(lat).toFixed(6) + ', ' + parseFloat(lon).toFixed(6));
                                this.searchResults = [];
                                this.searchQuery = displayName;
                            },
                            
                            getCurrentLocation() {
                                this.isLoadingLocation = true;
                                if (navigator.geolocation) {
                                    navigator.geolocation.getCurrentPosition(
                                        (position) => {
                                            let lat = position.coords.latitude.toFixed(6);
                                            let lng = position.coords.longitude.toFixed(6);
                                            $wire.set('koordinat', lat + ', ' + lng);
                                            this.isLoadingLocation = false;
                                            if(this.map) {
                                                let newLatLng = new L.LatLng(lat, lng);
                                                this.map.setView(newLatLng, 16);
                                                if(this.marker) this.marker.setLatLng(newLatLng);
                                            }
                                        },
                                        (error) => { alert('GPS Error'); this.isLoadingLocation = false; },
                                        { enableHighAccuracy: true }
                                    );
                                } else { this.isLoadingLocation = false; }
                            },
                            
                            initMap() {
                                this.showMap = !this.showMap;
                                if (this.showMap && !this.map) {
                                    setTimeout(() => {
                                        let currentVal = $wire.koordinat;
                                        let startLat = -0.789275, startLng = 113.921327, zoomLevel = 5;
                                        
                                        // Jika user sudah pilih cabang tapi koordinat masih kosong, asumsikan koordinat cabang?
                                        // (Bisa dikembangkan nanti, sementara kita pakai default atau existing)
                                        if(currentVal && currentVal.includes(',')) {
                                            let parts = currentVal.split(',');
                                            startLat = parseFloat(parts[0]);
                                            startLng = parseFloat(parts[1]);
                                            zoomLevel = 17; // Zoom lebih dalam untuk Gedung
                                        }

                                        this.map = L.map('mapPickerGedung').setView([startLat, startLng], zoomLevel);
                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                            attribution: '© OpenStreetMap'
                                        }).addTo(this.map);
                                        this.marker = L.marker([startLat, startLng], {draggable: true}).addTo(this.map);

                                        this.marker.on('dragend', (e) => {
                                            let position = this.marker.getLatLng();
                                            $wire.set('koordinat', position.lat.toFixed(6) + ', ' + position.lng.toFixed(6));
                                        });

                                        this.map.on('click', (e) => {
                                            this.marker.setLatLng(e.latlng);
                                            $wire.set('koordinat', e.latlng.lat.toFixed(6) + ', ' + e.latlng.lng.toFixed(6));
                                        });
                                        this.map.invalidateSize();
                                    }, 200);
                                }
                            }
                         }" 
                         class="p-4 bg-slate-50 border border-slate-200 rounded-2xl relative">
                        
                        <label class="block text-sm font-bold text-slate-700 mb-2">Koordinat Maps Gedung (Opsional)</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <input wire:model="koordinat" type="text" class="w-full pl-10 pr-4 py-2.5 rounded-xl border-slate-300 focus:ring-blue-500 font-mono text-sm shadow-sm" placeholder="Latitude, Longitude">
                                <div class="absolute left-3 top-3 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                </div>
                            </div>
                            
                            <div class="flex gap-2 shrink-0">
                                <button type="button" @click="getCurrentLocation()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-xl text-sm font-bold flex items-center gap-2">
                                    <svg x-show="!isLoadingLocation" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                                    GPS
                                </button>
                                <button type="button" @click="initMap()" class="px-4 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 border border-blue-200 rounded-xl text-sm font-bold flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                                    <span x-text="showMap ? 'Tutup Peta' : 'Buka Peta'"></span>
                                </button>
                            </div>
                        </div>
                        
                        <div x-show="showMap" style="display: none;" class="mt-4 relative" wire:ignore>
                            <div class="mb-3 relative z-[500]">
                                <div class="flex gap-2">
                                    <input x-model="searchQuery" @keydown.enter.prevent="searchLocation()" type="text" class="w-full px-4 py-2.5 border-slate-300 rounded-xl text-sm focus:ring-blue-500 shadow-sm" placeholder="Cari area gedung...">
                                    <button type="button" @click="searchLocation()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-sm font-bold flex items-center shadow-sm">Cari</button>
                                </div>
                                <div x-show="searchResults.length > 0" @click.away="searchResults = []" style="display:none;" class="absolute z-[600] w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden max-h-48 overflow-y-auto">
                                    <ul class="divide-y divide-slate-100">
                                        <template x-for="result in searchResults" :key="result.place_id">
                                            <li @click="selectLocation(result.lat, result.lon, result.display_name)" class="px-4 py-2.5 text-xs hover:bg-blue-50 cursor-pointer text-slate-700" x-text="result.display_name"></li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                            <div class="border-2 border-blue-200 rounded-xl overflow-hidden relative">
                                <div id="mapPickerGedung" class="w-full h-56 z-[100]"></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-bold text-slate-600 transition-colors">Batal</button>
                        <button type="submit" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg transition-all">{{ $isEditMode ? 'Simpan Perubahan' : 'Tambahkan Gedung' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>