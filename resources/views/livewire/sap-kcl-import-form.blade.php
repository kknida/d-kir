<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\SapKcl;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

new class extends Component {
    use WithFileUploads;

    public $file;
    public $step = 1; // 1: Upload, 2: Preview
    
    public $selectedUpdates = [];
    public $selectAll = true;

    public $countNew = 0;
    public $countConflicts = 0;
    public $countUnchanged = 0;

    // Smart Mapper Helper (Sama seperti di Asset)
    private function getRawValue($row, $keys) {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return null;
    }

    private function parseNumber($value) {
        if ($value === null || $value === '') return 0;
        if (is_numeric($value)) return (float) $value;
        $clean = preg_replace('/[^-0-9\.]/', '', str_replace(',', '', $value));
        return (float) $clean;
    }

    public function analyzeFile()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $data = Excel::toArray(new class implements ToArray, WithHeadingRow {
            public function array(array $array) { return $array; }
        }, $this->file->getRealPath());

        $rows = $data[0] ?? [];
        $isDbEmpty = SapKcl::count() === 0;

        if ($isDbEmpty) {
            $insertData = [];
            foreach ($rows as $row) {
                $sku = $this->getRawValue($row, ['material', 'sku', 'material_number']);
                if(empty($sku)) continue;
                $insertData[] = $this->mapRowToDb($row, $sku);
            }
            
            foreach (array_chunk($insertData, 500) as $chunk) {
                SapKcl::insert($chunk);
            }

            $this->resetForm();
            session()->flash('success', 'Database kosong. ' . count($insertData) . ' data KCL berhasil diimport!');
            $this->dispatch('import-finished');
            return;
        }

        $newRecords = [];
        $conflictedRecords = [];
        $unchangedCount = 0;

        $existingMaterials = SapKcl::all()->keyBy('material');

        foreach ($rows as $row) {
            $sku = $this->getRawValue($row, ['material', 'sku', 'material_number']);
            if(empty($sku)) continue;

            if (!$existingMaterials->has($sku)) {
                // DATA BARU
                $newRecords[] = $this->mapRowToDb($row, $sku);
            } else {
                // CEK KONFLIK
                $existing = $existingMaterials->get($sku);
                $newRowMapped = $this->mapRowToDb($row, $sku);
                
                $hasChanged = (
                    $existing->unrestricted != $newRowMapped['unrestricted'] ||
                    $existing->plant != $newRowMapped['plant'] ||
                    $existing->material_description != $newRowMapped['material_description']
                );

                if ($hasChanged) {
                    $conflictedRecords[] = [
                        'sku' => $sku,
                        'old' => [
                            'plant' => $existing->plant,
                            'desc' => $existing->material_description,
                            'stok' => $existing->unrestricted,
                        ],
                        'new' => [
                            'plant' => $newRowMapped['plant'],
                            'desc' => $newRowMapped['material_description'],
                            'stok' => $newRowMapped['unrestricted'],
                        ],
                        'raw_new_data' => $newRowMapped
                    ];
                } else {
                    $unchangedCount++;
                }
            }
        }

        $cacheKey = 'import_sap_' . auth()->id();
        Cache::put($cacheKey, [
            'new' => $newRecords,
            'conflicts' => $conflictedRecords
        ], now()->addMinutes(30));

        $this->countNew = count($newRecords);
        $this->countConflicts = count($conflictedRecords);
        $this->countUnchanged = $unchangedCount;
        
        $this->selectedUpdates = collect($conflictedRecords)->pluck('sku')->toArray();
        
        if ($this->countNew === 0 && $this->countConflicts === 0) {
            $this->resetForm();
            session()->flash('info', 'Tidak ada material baru atau perubahan yang ditemukan.');
            return;
        }

        $this->step = 2;
    }

    public function processImport()
    {
        $cacheKey = 'import_sap_' . auth()->id();
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            session()->flash('error', 'Sesi habis. Silakan upload ulang file.');
            $this->resetForm();
            return;
        }

        if (!empty($cachedData['new'])) {
            foreach (array_chunk($cachedData['new'], 500) as $chunk) {
                SapKcl::insert($chunk);
            }
        }

        $updatesCount = 0;
        if (!empty($cachedData['conflicts'])) {
            foreach ($cachedData['conflicts'] as $conflict) {
                if (in_array($conflict['sku'], $this->selectedUpdates)) {
                    SapKcl::where('material', $conflict['sku'])
                          ->update($conflict['raw_new_data']);
                    $updatesCount++;
                }
            }
        }

        Cache::forget($cacheKey);
        $this->resetForm();
        
        session()->flash('success', "Import Selesai! Ditambahkan: {$this->countNew} material baru. Diperbarui: {$updatesCount} material.");
        $this->dispatch('import-finished');
    }

    public function cancelImport()
    {
        Cache::forget('import_sap_' . auth()->id());
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->step = 1;
        $this->file = null;
        $this->countNew = 0;
        $this->countConflicts = 0;
        $this->countUnchanged = 0;
    }

    private function mapRowToDb($row, $sku)
    {
        return [
            'plant'                  => $this->getRawValue($row, ['plant', 'plnt']),
            'material'               => $sku,
            'material_description'   => $this->getRawValue($row, ['material_description', 'description', 'desc']),
            'storage_location'       => $this->getRawValue($row, ['storage_location', 'sloc']),
            'batch'                  => $this->getRawValue($row, ['batch']),
            'unrestricted'           => $this->parseNumber($this->getRawValue($row, ['unrestricted', 'unrestricted_use', 'stok'])),
            'currency'               => $this->getRawValue($row, ['currency', 'crcy']),
            'name_1'                 => $this->getRawValue($row, ['name_1', 'name']),
            'material_type'          => $this->getRawValue($row, ['material_type', 'mtart']),
            'material_group'         => $this->getRawValue($row, ['material_group', 'matl_group']),
            'value_unrestricted'     => $this->parseNumber($this->getRawValue($row, ['value_unrestricted', 'value'])),
            'descr_of_storage_loc'   => $this->getRawValue($row, ['descr_of_storage_loc', 'sloc_desc']),
            'base_unit_of_measure'   => $this->getRawValue($row, ['base_unit_of_measure', 'bun', 'uom']),
            'created_at'             => now(),
            'updated_at'             => now(),
        ];
    }
}; ?>

<div>
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="text-sm font-bold">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span class="text-sm font-bold">{{ session('error') }}</span>
        </div>
    @endif
    @if(session('info'))
        <div class="mb-6 p-4 bg-blue-50 border border-blue-100 text-blue-700 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span class="text-sm font-bold">{{ session('info') }}</span>
        </div>
    @endif

    @if($step == 1)
        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
            <form wire:submit="analyzeFile">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <div class="relative w-full md:w-1/2">
                        <label class="flex items-center justify-center w-full h-24 border-2 border-dashed rounded-3xl cursor-pointer transition-all {{ $file ? 'border-blue-400 bg-blue-50' : 'border-slate-200 bg-slate-50 hover:bg-slate-100' }}">
                            <div class="flex items-center gap-4 px-4 text-center">
                                @if(!$file)
                                    <div class="flex items-center gap-3">
                                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                        <p class="text-sm text-slate-500 font-medium">Klik untuk pilih file KCL Excel</p>
                                    </div>
                                @else
                                    <div class="flex items-center gap-3">
                                        <svg class="w-8 h-8 text-blue-600 animate-bounce" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h2a2 2 0 002-2V4a2 2 0 00-2-2H9zM11 12H9v-2h2v2zm0-4H9V4h2v4z"></path></svg>
                                        <p class="text-sm text-blue-700 font-bold">{{ $file->getClientOriginalName() }}</p>
                                    </div>
                                @endif
                            </div>
                            <input type="file" wire:model="file" class="hidden" accept=".xlsx,.xls,.csv" required />
                        </label>
                    </div>

                    <div class="w-full md:w-auto flex-1">
                        <button type="submit" wire:loading.attr="disabled"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-10 py-4 rounded-2xl font-bold shadow-lg shadow-blue-200 transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                            <span wire:loading.remove wire:target="analyzeFile">Analisa Data KCL</span>
                            <span wire:loading wire:target="analyzeFile">Memproses...</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    @if($step == 2)
        @php
            $cachedData = Cache::get('import_sap_' . auth()->id());
            $newRecords = $cachedData['new'] ?? [];
            $conflicts = $cachedData['conflicts'] ?? [];
        @endphp

        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h3 class="font-bold text-xl text-slate-800">Ringkasan Import KCL</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        <span class="font-bold text-emerald-600">{{ $countNew }} Material Baru</span> | 
                        <span class="font-bold text-amber-600">{{ $countConflicts }} Berubah</span> | 
                        <span class="font-bold text-slate-400">{{ $countUnchanged }} Data Sama</span>
                    </p>
                </div>
                <div class="flex gap-3">
                    <button wire:click="cancelImport" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">Batal</button>
                    <button wire:click="processImport" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg transition-colors">
                        Simpan ke Database
                    </button>
                </div>
            </div>

            @if($countNew > 0)
                <div class="p-6 border-b border-slate-100">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="font-bold text-emerald-600 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Terdapat {{ $countNew }} Material Baru (Akan Ditambahkan)
                        </h4>
                    </div>

                    <div class="overflow-y-auto max-h-72 border border-slate-200 rounded-2xl bg-slate-50/50">
                        <table class="w-full text-left text-sm relative">
                            <thead class="bg-slate-100 text-slate-600 font-bold sticky top-0 shadow-sm z-10">
                                <tr>
                                    <th class="p-4">SKU / Material</th>
                                    <th class="p-4">Deskripsi</th>
                                    <th class="p-4">Plant & SLoc</th>
                                    <th class="p-4 text-right">Stok Unrestricted</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($newRecords as $new)
                                    <tr class="hover:bg-emerald-50/30 transition-colors">
                                        <td class="p-4 font-bold text-slate-800">{{ $new['material'] }}</td>
                                        <td class="p-4 text-slate-600">{{ $new['material_description'] }}</td>
                                        <td class="p-4 text-slate-500">
                                            <span class="font-medium text-slate-700">{{ $new['plant'] }}</span>
                                            @if($new['storage_location']) / {{ $new['storage_location'] }} @endif
                                        </td>
                                        <td class="p-4 font-extrabold text-emerald-600 text-right">
                                            {{ number_format($new['unrestricted'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($countConflicts > 0)
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="font-bold text-amber-600 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Terdapat {{ $countConflicts }} Material Berubah (Pilih untuk Update)
                        </h4>
                    </div>

                    <div class="overflow-y-auto max-h-72 border border-slate-200 rounded-2xl bg-slate-50/50">
                        <table class="w-full text-left text-sm relative">
                            <thead class="bg-slate-100 text-slate-600 font-bold sticky top-0 shadow-sm z-10">
                                <tr>
                                    <th class="p-4 w-10 text-center"></th>
                                    <th class="p-4">SKU / Material</th>
                                    <th class="p-4">Data Database Saat Ini (Lama)</th>
                                    <th class="p-4">Data Excel Baru</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($conflicts as $conflict)
                                    <tr class="hover:bg-amber-50/30 transition-colors">
                                        <td class="p-4 text-center">
                                            <input type="checkbox" wire:model="selectedUpdates" value="{{ $conflict['sku'] }}" class="rounded text-blue-600 focus:ring-blue-500 w-5 h-5">
                                        </td>
                                        <td class="p-4 font-bold text-slate-800">{{ $conflict['sku'] }}</td>
                                        <td class="p-4 text-slate-500">
                                            <div class="line-through decoration-rose-300 opacity-75">
                                                Plant: {{ $conflict['old']['plant'] }} <br>
                                                Stok: {{ $conflict['old']['stok'] }} <br>
                                                Desc: {{ $conflict['old']['desc'] }}
                                            </div>
                                        </td>
                                        <td class="p-4 font-medium text-amber-700">
                                            <div>
                                                Plant: {{ $conflict['new']['plant'] }} <br>
                                                Stok: {{ $conflict['new']['stok'] }} <br>
                                                Desc: {{ $conflict['new']['desc'] }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>