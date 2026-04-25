<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\SapKcl;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    // Filter & Search
    public $search = '';
    public $perPage = 10;
    public $filterPlant = '';

    // State Modal (Create & Edit)
    public $isCreateModalOpen = false;
    public $isEditModalOpen = false;
    public $selectedId = null;

    // Form Fields (Sesuai Database sap_kcls)
    public $plant, $material, $material_description, $storage_location, $batch;
    public $unrestricted = 0;
    public $currency = 'IDR';
    public $name_1, $material_type, $material_group;
    public $value_unrestricted = 0;
    public $descr_of_storage_loc, $base_unit_of_measure;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterPlant' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterPlant() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    // --- FUNGSI CREATE ---
    public function openCreateModal()
    {
        $this->resetForm();
        $this->isCreateModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'plant' => 'required',
            'material' => 'required|unique:sap_kcls,material',
            'unrestricted' => 'numeric',
            'value_unrestricted' => 'numeric',
        ]);

        SapKcl::create($this->mapFields());

        $this->isCreateModalOpen = false;
        session()->flash('success', 'Data KCL baru berhasil ditambahkan.');
    }

    // --- FUNGSI EDIT ---
    public function edit($id)
    {
        $data = SapKcl::findOrFail($id);
        $this->selectedId = $id;
        $this->fill($data->toArray());
        $this->isEditModalOpen = true;
    }

    public function update()
    {
        $this->validate([
            'plant' => 'required',
            'material' => 'required|unique:sap_kcls,material,' . $this->selectedId,
        ]);

        SapKcl::findOrFail($this->selectedId)->update($this->mapFields());

        $this->isEditModalOpen = false;
        session()->flash('success', 'Data KCL berhasil diperbarui.');
    }

    // --- FUNGSI HAPUS ---
    public function delete($id)
    {
        SapKcl::findOrFail($id)->delete();
        session()->flash('success', 'Data berhasil dihapus.');
    }

    public function closeModal()
    {
        $this->isCreateModalOpen = false;
        $this->isEditModalOpen = false;
    }

    private function resetForm()
    {
        $this->reset([
            'selectedId', 'plant', 'material', 'material_description', 
            'storage_location', 'batch', 'unrestricted', 'name_1', 
            'material_type', 'material_group', 'value_unrestricted', 
            'descr_of_storage_loc', 'base_unit_of_measure'
        ]);
        $this->unrestricted = 0;
        $this->value_unrestricted = 0;
        $this->currency = 'IDR';
    }

    private function mapFields()
    {
        return [
            'plant' => $this->plant,
            'material' => $this->material,
            'material_description' => $this->material_description,
            'storage_location' => $this->storage_location,
            'batch' => $this->batch,
            'unrestricted' => $this->unrestricted,
            'currency' => $this->currency,
            'name_1' => $this->name_1,
            'material_type' => $this->material_type,
            'material_group' => $this->material_group,
            'value_unrestricted' => $this->value_unrestricted,
            'descr_of_storage_loc' => $this->descr_of_storage_loc,
            'base_unit_of_measure' => $this->base_unit_of_measure,
        ];
    }

    #[On('import-finished')] 
    public function with(): array
    {
        $query = SapKcl::query();
        if ($this->search) {
            $query->where('material', 'like', "%{$this->search}%")
                  ->orWhere('material_description', 'like', "%{$this->search}%");
        }
        if ($this->filterPlant) {
            $query->where('plant', $this->filterPlant);
        }

        return [
            'items' => $query->latest()->paginate($this->perPage),
            'plants' => SapKcl::select('plant')->whereNotNull('plant')->distinct()->pluck('plant'),
        ];
    }
}; ?>

<div class="space-y-4">
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative w-full md:w-64">
                <input wire:model.live.debounce.300ms="search" type="text" 
                       class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500" 
                       placeholder="Cari SKU / Deskripsi...">
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <select wire:model.live="filterPlant" class="bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Plant</option>
                @foreach($plants as $p) <option value="{{ $p }}">{{ $p }}</option> @endforeach
            </select>
        </div>

        <button wire:click="openCreateModal" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-100 transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Data
        </button>
    </div>

    @if(session()->has('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl text-sm font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100 text-xs uppercase text-slate-400 font-bold">
                    <tr>
                        <th class="px-6 py-4">Plant</th>
                        <th class="px-6 py-4">Material / SKU</th>
                        <th class="px-6 py-4">Deskripsi</th>
                        <th class="px-6 py-4 text-right">Stok</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse($items as $item)
                    <tr class="hover:bg-blue-50/30 transition-colors">
                        <td class="px-6 py-4 text-slate-500">{{ $item->plant }}</td>
                        <td class="px-6 py-4 font-bold text-slate-800">{{ $item->material }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $item->material_description }}</td>
                        <td class="px-6 py-4 text-right font-extrabold text-blue-600">{{ number_format($item->unrestricted, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-2">
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-amber-500 hover:bg-amber-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus {{ $item->material }}?" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">Data kosong.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">{{ $items->links() }}</div>
    </div>

    @if($isCreateModalOpen || $isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ $isCreateModalOpen ? 'Tambah Data KCL' : 'Edit Data KCL' }}</h3>
                <button wire:click="closeModal" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Plant *</label>
                        <input wire:model="plant" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                        @error('plant') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Material *</label>
                        <input wire:model="material" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                        @error('material') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Deskripsi Material</label>
                    <textarea wire:model="material_description" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Material Type</label>
                        <input wire:model="material_type" type="text" placeholder="Contoh: ZADM, ZOPR" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Material Group</label>
                        <input wire:model="material_group" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                    </div>
                </div>  

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Name 1</label>
                        <input wire:model="name_1" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Unit (Base UoM)</label>
                        <input wire:model="base_unit_of_measure" type="text" placeholder="Contoh: PC, UNIT" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Storage Location (SLoc)</label>
                        <input wire:model="storage_location" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500" placeholder="Contoh: 0008">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Keterangan SLoc</label>
                        <input wire:model="descr_of_storage_loc" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Batch</label>
                        <input wire:model="batch" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Stok Unrestricted</label>
                        <input wire:model="unrestricted" type="number" step="0.01" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500">
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button wire:click="closeModal" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Batal</button>
                    <button wire:click="{{ $isCreateModalOpen ? 'store' : 'update' }}" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg shadow-blue-100">
                        {{ $isCreateModalOpen ? 'Simpan Data' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>