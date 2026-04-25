<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Cabang;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    public $isModalOpen = false;
    public $isEditMode = false;
    public $selectedId = null;

    public $nama = '', $keterangan = '', $koordinat = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatedSearch() { $this->resetPage(); }

    public function openModal()
    {
        $this->reset(['selectedId', 'nama', 'keterangan', 'koordinat', 'isEditMode']);
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $cabang = Cabang::findOrFail($id);
        $this->selectedId = $id;
        $this->nama = $cabang->nama;
        $this->keterangan = $cabang->keterangan;
        $this->koordinat = $cabang->koordinat;
        
        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'nama' => 'required|string|max:255|unique:cabangs,nama',
            'keterangan' => 'nullable|string',
            'koordinat' => 'nullable|string',
        ]);

        Cabang::create([
            'nama' => $this->nama,
            'keterangan' => $this->keterangan,
            'koordinat' => $this->koordinat,
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Cabang baru berhasil ditambahkan.');
    }

    public function update()
    {
        $this->validate([
            'nama' => 'required|string|max:255|unique:cabangs,nama,' . $this->selectedId,
        ]);

        Cabang::findOrFail($this->selectedId)->update([
            'nama' => $this->nama,
            'keterangan' => $this->keterangan,
            'koordinat' => $this->koordinat,
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Data Cabang berhasil diperbarui.');
    }

    public function delete($id)
    {
        $cabang = Cabang::findOrFail($id);
        if (\App\Models\Gedung::where('cabang_id', $id)->exists()) {
            session()->flash('error', "Gagal dihapus: Cabang '{$cabang->nama}' masih memiliki data Gedung aktif.");
            return;
        }

        $cabang->delete();
        session()->flash('success', 'Cabang berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'items' => Cabang::where('nama', 'like', "%{$this->search}%")
                ->orWhere('keterangan', 'like', "%{$this->search}%")
                ->latest()
                ->paginate($this->perPage),
        ];
    }
}; ?>

<div class="space-y-4">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
        <div class="relative w-full sm:w-80">
            <input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500" placeholder="Cari Nama Cabang...">
            <div class="absolute left-3 top-2.5 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        <button wire:click="openModal" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg transition-all flex items-center justify-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Cabang
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Nama Cabang</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4">Koordinat Maps</th>
                        <th class="px-6 py-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $item->nama }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $item->keterangan ?: '-' }}</td>
                            <td class="px-6 py-4">
                                @if($item->koordinat)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ str_replace(' ', '', $item->koordinat) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-mono font-bold transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        {{ $item->koordinat }}
                                    </a>
                                @else
                                    <span class="text-slate-400 italic text-xs">Belum diatur</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    <button wire:click="edit({{ $item->id }})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                    <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus Cabang ini?" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400">Belum ada data cabang.</td></tr>
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
                <h3 class="font-bold text-xl text-slate-800">{{ $isEditMode ? 'Edit Cabang' : 'Tambah Cabang Baru' }}</h3>
                <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-rose-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-visible max-h-[85vh]">
                <form wire:submit="{{ $isEditMode ? 'update' : 'store' }}" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nama Cabang <span class="text-rose-500">*</span></label>
                        <input wire:model="nama" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500" placeholder="Misal: Kantor Pusat Jakarta, Cabang Surabaya">
                        @error('nama') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Keterangan Singkat</label>
                        <textarea wire:model="keterangan" rows="2" class="w-full rounded-xl border-slate-200 focus:ring-blue-500" placeholder="Informasi tambahan..."></textarea>
                    </div>

                    <div x-data="{
                            showMap: false,
                            map: null,
                            marker: null,
                            isLoadingLocation: false,
                            
                            // State Pencarian Geocoding
                            searchQuery: '',
                            isSearching: false,
                            searchResults: [],
                            
                            // Method Cari Lokasi via OpenStreetMap Nominatim
                            async searchLocation() {
                                if (!this.searchQuery) return;
                                this.isSearching = true;
                                this.searchResults = [];
                                
                                try {
                                    let response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&limit=5&countrycodes=id`);
                                    let data = await response.json();
                                    
                                    if(data.length > 0) {
                                        this.searchResults = data;
                                    } else {
                                        alert('Lokasi tidak ditemukan. Coba gunakan nama kota atau kecamatan yang lebih umum.');
                                    }
                                } catch (e) {
                                    alert('Terjadi kesalahan saat mencari lokasi.');
                                }
                                this.isSearching = false;
                            },

                            // Saat user memilih salah satu hasil pencarian
                            selectLocation(lat, lon, displayName) {
                                let newLat = parseFloat(lat);
                                let newLon = parseFloat(lon);
                                let newLatLng = new L.LatLng(newLat, newLon);
                                
                                // Pindahkan Peta
                                this.map.setView(newLatLng, 16);
                                if(this.marker) this.marker.setLatLng(newLatLng);
                                
                                // Update Input Livewire
                                $wire.set('koordinat', newLat.toFixed(6) + ', ' + newLon.toFixed(6));
                                
                                // Bersihkan hasil pencarian & update teks bar
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
                                        (error) => {
                                            alert('Gagal mengambil lokasi. Pastikan izin lokasi (GPS) diaktifkan di browser.');
                                            this.isLoadingLocation = false;
                                        },
                                        { enableHighAccuracy: true }
                                    );
                                } else {
                                    this.isLoadingLocation = false;
                                }
                            },
                            
                            initMap() {
                                this.showMap = !this.showMap;
                                
                                if (this.showMap && !this.map) {
                                    setTimeout(() => {
                                        let currentVal = $wire.koordinat;
                                        // Default koordinat (Indonesia Tengah)
                                        let startLat = -0.789275;
                                        let startLng = 113.921327;
                                        let zoomLevel = 5;
                                        
                                        if(currentVal && currentVal.includes(',')) {
                                            let parts = currentVal.split(',');
                                            startLat = parseFloat(parts[0]);
                                            startLng = parseFloat(parts[1]);
                                            zoomLevel = 15;
                                        }

                                        this.map = L.map('mapPickerContainer').setView([startLat, startLng], zoomLevel);
                                        
                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                            attribution: '© OpenStreetMap contributors'
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
                        
                        <label class="block text-sm font-bold text-slate-700 mb-2">Koordinat Lokasi</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <input wire:model="koordinat" type="text" class="w-full pl-10 pr-4 py-2.5 rounded-xl border-slate-300 focus:ring-blue-500 font-mono text-sm" placeholder="Latitude, Longitude">
                                <div class="absolute left-3 top-3 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </div>
                            </div>
                            
                            <div class="flex gap-2 shrink-0">
                                <button type="button" @click="getCurrentLocation()" class="px-4 py-2.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-xl text-sm font-bold flex items-center gap-2 transition-colors">
                                    <svg x-show="!isLoadingLocation" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                                    <svg x-show="isLoadingLocation" class="w-4 h-4 text-emerald-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Saat Ini
                                </button>
                                
                                <button type="button" @click="initMap()" class="px-4 py-2.5 bg-blue-100 text-blue-700 hover:bg-blue-200 border border-blue-200 rounded-xl text-sm font-bold flex items-center gap-2 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                                    <span x-text="showMap ? 'Tutup Peta' : 'Buka Peta'"></span>
                                </button>
                            </div>
                        </div>
                        
                        <div x-show="showMap" style="display: none;" class="mt-4 relative" wire:ignore>
                            
                            <div class="mb-3 relative z-[500]">
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <input x-model="searchQuery" @keydown.enter.prevent="searchLocation()" type="text" class="w-full pl-10 pr-4 py-2.5 border-slate-300 rounded-xl text-sm focus:ring-blue-500 shadow-sm" placeholder="Cari Daerah, Kota, atau Kecamatan...">
                                        <div class="absolute left-3 top-3 text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                        </div>
                                    </div>
                                    <button type="button" @click="searchLocation()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm transition-colors">
                                        <span x-show="!isSearching">Cari</span>
                                        <svg x-show="isSearching" style="display:none;" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </button>
                                </div>

                                <div x-show="searchResults.length > 0" @click.away="searchResults = []" style="display:none;" class="absolute z-[600] w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden max-h-56 overflow-y-auto">
                                    <ul class="divide-y divide-slate-100">
                                        <template x-for="result in searchResults" :key="result.place_id">
                                            <li @click="selectLocation(result.lat, result.lon, result.display_name)" class="px-4 py-3 text-xs hover:bg-blue-50 cursor-pointer text-slate-700 leading-relaxed transition-colors flex items-start gap-2">
                                                <svg class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                <span x-text="result.display_name"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>

                            <div class="border-2 border-blue-200 rounded-xl overflow-hidden relative">
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-[400] bg-white/90 backdrop-blur px-4 py-1.5 rounded-full shadow-lg text-[10px] font-bold text-slate-700 flex items-center gap-1.5 pointer-events-none border border-slate-200">
                                    <svg class="w-3 h-3 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    Geser pin atau klik area peta
                                </div>
                                <div id="mapPickerContainer" class="w-full h-64 z-[100]"></div>
                            </div>
                        </div>

                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-bold text-slate-600 transition-colors">Batal</button>
                        <button type="submit" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg transition-all">{{ $isEditMode ? 'Simpan Perubahan' : 'Tambahkan Cabang' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>