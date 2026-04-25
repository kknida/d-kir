<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\SapAsset;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $filterClass = '';
    public $filterLocation = '';

    // Modal Edit State
    public $isModalOpen = false;
    public $editId = null;
    public $editAssetNo = '';
    public $editName = '';
    public $editLocation = '';
    public $editAcquisVal = 0;
    public $editBookVal = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterClass' => ['except' => ''],
        'filterLocation' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterClass() { $this->resetPage(); }
    public function updatedFilterLocation() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function delete($id)
    {
        SapAsset::findOrFail($id)->delete();
        session()->flash('success', 'Data Asset berhasil dihapus.');
    }

    public function edit($id)
    {
        $data = SapAsset::findOrFail($id);
        $this->editId = $data->id;
        $this->editAssetNo = $data->asset_number;
        $this->editName = $data->asset_name;
        $this->editLocation = $data->asset_location;
        $this->editAcquisVal = $data->acquis_val;
        $this->editDescription = $data->asset_description;
        $this->editDescription2 = $data->asset_description_2; // Load data lama
        $this->editBookVal = $data->book_val;
        
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    public function update()
    {
        $this->validate([
            'editName' => 'required',
            'editAcquisVal' => 'required|numeric',
            'editBookVal' => 'required|numeric',
        ]);

        SapAsset::where('id', $this->editId)->update([
            'asset_name' => $this->editName,
            'asset_location' => $this->editLocation,
            'acquis_val' => $this->editAcquisVal,
            'book_val' => $this->editBookVal,
            'asset_description' => $this->editDescription,
            'asset_description_2' => $this->editDescription2, // Simpan data baru
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Data Asset berhasil diperbarui.');
    }

    #[On('asset-import-finished')] 
    public function with(): array
    {
        $query = SapAsset::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('asset_number', 'like', '%' . $this->search . '%')
                  ->orWhere('asset_name', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterClass)) {
            $query->where('asset_class', $this->filterClass);
        }

        if (!empty($this->filterLocation)) {
            $query->where('asset_location', $this->filterLocation);
        }

        return [
            'items' => $query->latest()->paginate($this->perPage),
            'classes' => SapAsset::select('asset_class')->whereNotNull('asset_class')->distinct()->pluck('asset_class'),
            'locations' => SapAsset::select('asset_location')->whereNotNull('asset_location')->distinct()->pluck('asset_location'),
        ];
    }
}; ?>

<div class="space-y-4 relative">
    
    @if(session()->has('success'))
        <div class="p-4 mb-4 text-sm text-emerald-800 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-sm flex items-center gap-3"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
            <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="font-bold">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col lg:flex-row gap-4 items-center justify-between">
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
            
            <div class="relative w-full md:w-64">
                <input wire:model.live.debounce.300ms="search" type="text" 
                       class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500" 
                       placeholder="Cari No Asset / Nama...">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <select wire:model.live="filterClass" class="bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 pr-8">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterLocation" class="bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 pr-8">
                <option value="">Semua Lokasi</option>
                @foreach($locations as $l)
                    <option value="{{ $l }}">{{ $l }}</option>
                @endforeach
            </select>

            <select wire:model.live="perPage" class="bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 pr-8">
                <option value="10">10 Baris</option>
                <option value="25">25 Baris</option>
                <option value="50">50 Baris</option>
            </select>
        </div>
        
        <div wire:loading class="text-sm font-bold text-blue-600 animate-pulse hidden lg:block">
            Memuat data...
        </div>
    </div>

    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden relative">
        <div wire:loading.class.remove="hidden" class="hidden absolute inset-0 bg-white/50 backdrop-blur-[2px] z-10 flex items-center justify-center">
            <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-100 text-xs uppercase text-slate-500 font-extrabold">
                    <tr>
                        <th class="px-6 py-4">No Asset</th>
                        <th class="px-6 py-4">Sub No Asset</th>
                        <th class="px-6 py-4">Nama Asset</th>
                        <th class="px-6 py-4">Asset Description</th>
                        <th class="px-6 py-4">Lokasi</th>
                        <th class="px-6 py-4 text-right">Nilai Perolehan</th>
                        <th class="px-6 py-4 text-right">Nilai Buku</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($items as $item)
                    <tr class="hover:bg-blue-50/50 transition-colors group" wire:key="row-{{ $item->id }}">
                        <td class="px-6 py-4 font-bold text-slate-800">{{ $item->asset_number }}</td>
                        <td class="px-6 py-4 font-bold text-slate-800">{{ $item->sub_number }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ Str::limit($item->asset_name, 40) }}</td>
                        <td class="px-6 py-4">
                            <div class="text-slate-700 font-medium">{{ Str::limit($item->asset_description, 30) }}</div>
                            <div class="text-xs text-slate-400 italic">{{ Str::limit($item->asset_description_2, 30) }}</div>
                        </td>
                        <td class="px-6 py-4 text-slate-500">{{ $item->asset_location }}</td>
                        <td class="px-6 py-4 text-right font-medium text-slate-700">
                            {{ number_format($item->acquis_val, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-extrabold text-blue-600">
                            {{ number_format($item->book_val, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center items-center gap-2">
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-amber-500 hover:bg-amber-100 rounded-xl transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button wire:click="delete({{ $item->id }})" 
                                        wire:confirm="Yakin ingin menghapus Asset No: {{ $item->asset_number }}?"
                                        class="p-2 text-rose-500 hover:bg-rose-100 rounded-xl transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center text-slate-400">
                                <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0l-8 4-8-4"></path></svg>
                                <span class="font-medium">Data Asset tidak ditemukan.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4 bg-slate-50 border-t border-slate-100">
            {{ $items->links() }}
        </div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">Edit Asset: {{ $editAssetNo }}</h3>
                <button wire:click="closeModal" class="text-slate-400 hover:text-rose-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto">
                <form wire:submit="update" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Asset</label>
                        <input wire:model="editName" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Lokasi</label>
                        <input wire:model="editLocation" type="text" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Nilai Perolehan</label>
                            <input wire:model="editAcquisVal" type="number" step="0.01" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Nilai Buku</label>
                            <input wire:model="editBookVal" type="number" step="0.01" class="w-full rounded-xl border-slate-200 focus:ring-blue-500 text-sm">
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 mt-6">
                        <button type="button" wire:click="closeModal" class="px-5 py-2.5 text-sm font-bold bg-slate-100 hover:bg-slate-200 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>