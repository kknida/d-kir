<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\SapAsset;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

new class extends Component {
    use WithFileUploads;

    public $file;
    public $step = 1;
    
    public $selectedUpdates = [];
    public $selectAll = true;

    public $countNew = 0;
    public $countConflicts = 0;
    public $countUnchanged = 0;

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
        $isDbEmpty = SapAsset::count() === 0;

        if ($isDbEmpty) {
            $insertData = [];
            foreach ($rows as $row) {
                $assetNo = $this->getRawValue($row, ['asset', 'asset_number', 'asset_main_no', 'nomor_aset']);
                if(empty($assetNo)) continue;
                
                $subNo = $this->getRawValue($row, ['sno', 'sno.', 'sub_number', 'sub_no']);
                $insertData[] = $this->mapRowToDb($row, $assetNo, $subNo);
            }
            
            if (count($insertData) === 0) {
                session()->flash('error', 'Gagal membaca data Excel. Pastikan header sudah sesuai.');
                $this->resetForm();
                return;
            }

            foreach (array_chunk($insertData, 500) as $chunk) {
                SapAsset::insert($chunk);
            }

            $this->resetForm();
            session()->flash('success', 'Database kosong. ' . count($insertData) . ' data Asset mentah SAP berhasil diimport!');
            $this->dispatch('asset-import-finished');
            return;
        }

        $newRecords = [];
        $conflictedRecords = [];
        $unchangedCount = 0;

        // Menggabungkan Asset Number dan Sub Number sebagai Composite Key
        $existingAssets = SapAsset::all()->keyBy(function($item) {
            return $item->asset_number . '_' . ($item->sub_number ?? '0');
        });

        foreach ($rows as $row) {
            $assetNo = $this->getRawValue($row, ['asset', 'asset_number', 'asset_main_no', 'nomor_aset']);
            if(empty($assetNo)) continue;

            $subNo = $this->getRawValue($row, ['sno', 'sno.', 'sub_number', 'sub_no']);
            $uniqueKey = $assetNo . '_' . ($subNo ?? '0');

            if (!$existingAssets->has($uniqueKey)) {
                $newRecords[] = $this->mapRowToDb($row, $assetNo, $subNo);
            } else {
                $existing = $existingAssets->get($uniqueKey);
                $newRowMapped = $this->mapRowToDb($row, $assetNo, $subNo);
                
                $hasChanged = (
                    $existing->book_val != $newRowMapped['book_val'] ||
                    $existing->acquis_val != $newRowMapped['acquis_val'] ||
                    $existing->asset_location != $newRowMapped['asset_location'] ||
                    $existing->asset_name != $newRowMapped['asset_name']
                );

                if ($hasChanged) {
                    $conflictedRecords[] = [
                        'unique_key' => $uniqueKey,
                        'asset_number' => $assetNo,
                        'sub_number' => $subNo,
                        'old' => [
                            'name' => $existing->asset_name,
                            'location' => $existing->asset_location,
                            'book_val' => $existing->book_val,
                        ],
                        'new' => [
                            'name' => $newRowMapped['asset_name'],
                            'location' => $newRowMapped['asset_location'],
                            'book_val' => $newRowMapped['book_val'],
                        ],
                        'raw_new_data' => $newRowMapped
                    ];
                } else {
                    $unchangedCount++;
                }
            }
        }

        $cacheKey = 'import_asset_' . auth()->id();
        Cache::put($cacheKey, [
            'new' => $newRecords,
            'conflicts' => $conflictedRecords
        ], now()->addMinutes(30));

        $this->countNew = count($newRecords);
        $this->countConflicts = count($conflictedRecords);
        $this->countUnchanged = $unchangedCount;
        
        $this->selectedUpdates = collect($conflictedRecords)->pluck('unique_key')->toArray();
        
        if ($this->countNew === 0 && $this->countConflicts === 0) {
            $this->resetForm();
            session()->flash('info', 'Tidak ada asset baru atau perubahan nilai yang ditemukan.');
            return;
        }

        $this->step = 2;
    }

    public function processImport()
    {
        $cacheKey = 'import_asset_' . auth()->id();
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            session()->flash('error', 'Sesi import habis. Silakan upload ulang file.');
            $this->resetForm();
            return;
        }

        if (!empty($cachedData['new'])) {
            foreach (array_chunk($cachedData['new'], 500) as $chunk) {
                SapAsset::insert($chunk);
            }
        }

        $updatesCount = 0;
        if (!empty($cachedData['conflicts'])) {
            foreach ($cachedData['conflicts'] as $conflict) {
                if (in_array($conflict['unique_key'], $this->selectedUpdates)) {
                    SapAsset::where('asset_number', $conflict['asset_number'])
                          ->where('sub_number', $conflict['sub_number'])
                          ->update($conflict['raw_new_data']);
                    $updatesCount++;
                }
            }
        }

        Cache::forget($cacheKey);
        $this->resetForm();
        
        session()->flash('success', "Import SAP Selesai! Ditambahkan: {$this->countNew} asset baru. Diperbarui: {$updatesCount} asset.");
        $this->dispatch('asset-import-finished');
    }

    public function cancelImport()
    {
        Cache::forget('import_asset_' . auth()->id());
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

    private function mapRowToDb($row, $assetNo, $subNo)
    {
        $capitalizedOn = null;
        $capDateRaw = $this->getRawValue($row, ['capitalized_on', 'cap_date', 'capital_date']);
        if (!empty($capDateRaw)) {
            try {
                if (is_numeric($capDateRaw)) {
                    $capitalizedOn = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($capDateRaw)->format('Y-m-d');
                } else {
                    $capitalizedOn = Carbon::parse($capDateRaw)->format('Y-m-d');
                }
            } catch (\Exception $e) { $capitalizedOn = null; }
        }

        return [
            'asset_number'           => $assetNo,
            'sub_number'             => $subNo,
            'asset_name'             => $this->getRawValue($row, ['asset_name']),
            'original_asset'         => $this->getRawValue($row, ['original_asset', 'orig_asset']),
            'asset_description'      => $this->getRawValue($row, ['asset_description', 'description']),
            'asset_description_2'    => $this->getRawValue($row, ['asset_description_2', 'description_2', 'inventory_note', 'text']),
            'asset_main_no_text'     => $this->getRawValue($row, ['asset_main_no_text']),
            'asset_class'            => $this->getRawValue($row, ['asset_class', 'class']),
            'asset_location'         => $this->getRawValue($row, ['location', 'asset_location', 'loc']),
            'capitalized_on'         => $capitalizedOn,
            'acquis_val'             => $this->parseNumber($this->getRawValue($row, ['acquisval', 'acquis_val', 'acq_value'])),
            'accum_dep'              => $this->parseNumber($this->getRawValue($row, ['accumdep', 'accum_dep'])),
            'book_val'               => $this->parseNumber($this->getRawValue($row, ['book_val', 'book_value'])),
            'quantity'               => $this->parseNumber($this->getRawValue($row, ['quantity', 'qty'])),
            'base_unit_of_measure'   => $this->getRawValue($row, ['base_unit_of_measure', 'uom', 'unit']),
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
                                        <p class="text-sm text-slate-500 font-medium">Upload File Mentah SAP Excel</p>
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
                            <span wire:loading.remove wire:target="analyzeFile">Analisa Data Asset</span>
                            <span wire:loading wire:target="analyzeFile">Memproses...</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    @if($step == 2)
        @php
            $cachedData = Cache::get('import_asset_' . auth()->id());
            $newRecords = $cachedData['new'] ?? [];
            $conflicts = $cachedData['conflicts'] ?? [];
        @endphp

        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h3 class="font-bold text-xl text-slate-800">Preview Import Asset</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        <span class="font-bold text-emerald-600">{{ $countNew }} Asset Baru</span> | 
                        <span class="font-bold text-amber-600">{{ $countConflicts }} Berubah</span> | 
                        <span class="font-bold text-slate-400">{{ $countUnchanged }} Data Sama</span>
                    </p>
                </div>
                <div class="flex gap-3">
                    <button wire:click="cancelImport" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">Batal</button>
                    <button wire:click="processImport" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg transition-colors">
                        Update Database
                    </button>
                </div>
            </div>

            @if($countNew > 0)
                <div class="p-6 border-b border-slate-100">
                    <div class="mb-4">
                        <h4 class="font-bold text-emerald-600 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Terdapat {{ $countNew }} Asset Baru (Akan Ditambahkan)
                        </h4>
                    </div>
                    <div class="overflow-y-auto max-h-72 border border-slate-200 rounded-2xl bg-slate-50/50">
                        <table class="w-full text-left text-sm relative">
                            <thead class="bg-slate-100 text-slate-600 font-bold sticky top-0 shadow-sm z-10">
                                <tr>
                                    <th class="p-4">Asset & Sub</th>
                                    <th class="p-4">Nama Asset</th>
                                    <th class="p-4">Lokasi</th>
                                    <th class="p-4 text-right">Nilai Buku</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($newRecords as $new)
                                    <tr class="hover:bg-emerald-50/30">
                                        <td class="p-4 font-bold text-slate-800">{{ $new['asset_number'] }} <span class="text-slate-400 text-xs">- {{ $new['sub_number'] }}</span></td>
                                        <td class="p-4 text-slate-600">{{ $new['asset_name'] }}</td>
                                        <td class="p-4 text-slate-500">{{ $new['asset_location'] }}</td>
                                        <td class="p-4 font-extrabold text-emerald-600 text-right">Rp {{ number_format($new['book_val'], 0, ',', '.') }}</td>
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
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Terdapat {{ $countConflicts }} Data Asset Berubah (Pilih untuk Update)
                        </h4>
                    </div>

                    <div class="overflow-y-auto max-h-72 border border-slate-200 rounded-2xl bg-slate-50/50">
                        <table class="w-full text-left text-sm relative">
                            <thead class="bg-slate-100 text-slate-600 font-bold sticky top-0 shadow-sm z-10">
                                <tr>
                                    <th class="p-4 w-10 text-center"></th>
                                    <th class="p-4">Asset & Sub</th>
                                    <th class="p-4">Data Lama (DB)</th>
                                    <th class="p-4">Data Baru (Excel)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($conflicts as $conflict)
                                    <tr class="hover:bg-amber-50/30">
                                        <td class="p-4 text-center">
                                            <input type="checkbox" wire:model="selectedUpdates" value="{{ $conflict['unique_key'] }}" class="rounded text-blue-600 w-5 h-5">
                                        </td>
                                        <td class="p-4 font-bold text-slate-800">{{ $conflict['asset_number'] }} <span class="text-slate-400 text-xs">- {{ $conflict['sub_number'] }}</span></td>
                                        <td class="p-4 text-slate-500">
                                            <div class="line-through decoration-rose-300 opacity-75">
                                                {{ $conflict['old']['name'] }} <br>
                                                Lokasi: {{ $conflict['old']['location'] }} <br>
                                                Nilai Buku: Rp {{ number_format($conflict['old']['book_val'], 0, ',', '.') }}
                                            </div>
                                        </td>
                                        <td class="p-4 font-medium text-amber-700">
                                            <div>
                                                {{ $conflict['new']['name'] }} <br>
                                                Lokasi: {{ $conflict['new']['location'] }} <br>
                                                Nilai Buku: Rp {{ number_format($conflict['new']['book_val'], 0, ',', '.') }}
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