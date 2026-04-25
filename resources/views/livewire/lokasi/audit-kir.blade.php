<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Ruangan;
use App\Models\Barang;
use App\Models\Kir;
use App\Models\MutasiBarang;
use App\Models\KondisiHistory;

new class extends Component {
    use WithFileUploads;

    public Ruangan $ruangan;
    public $scanKode = ''; // Input untuk nembak QR Barang

    // State untuk Audit Kondisi
    public $isModalKondisiOpen = false;
    public $selectedKirId = null;
    public $kondisi_baru = '';
    public $foto_bukti;
    public $keterangan_audit = '';

    public function mount(Ruangan $ruangan)
    {
        $this->ruangan = $ruangan;
    }

    // ----------------------------------------------------
    // FITUR 1: SCAN CHECK-IN (OTOMATIS CATAT MUTASI)
    // ----------------------------------------------------
    public function checkInBarang()
    {
        $this->validate(['scanKode' => 'required']);

        $barang = Barang::where('kode_inventaris', $this->scanKode)->first();

        if (!$barang) {
            session()->flash('error', "Aset {$this->scanKode} tidak ditemukan di database!");
            $this->scanKode = '';
            return;
        }

        // Cek apakah barang ini sudah ada di tabel KIR
        $existingKir = Kir::where('barang_id', $barang->id)->first();
        $userId = auth()->id() ?? 1; // Default 1 jika tidak ada fitur login sementara waktu

        if ($existingKir) {
            // Jika barang sudah ada di ruangan ini, batalkan
            if ($existingKir->ruangan_id == $this->ruangan->id) {
                session()->flash('error', "Aset ini sudah terdaftar di ruangan ini.");
                $this->scanKode = '';
                return;
            }

            // Jika barang berasal dari ruangan LAIN -> CATAT MUTASI!
            MutasiBarang::create([
                'barang_id' => $barang->id,
                'ruangan_asal_id' => $existingKir->ruangan_id,
                'ruangan_tujuan_id' => $this->ruangan->id,
                'user_id' => $userId,
                'tanggal_mutasi' => now(),
                'alasan_mutasi' => 'Dipindahkan via Aplikasi Scanner KIR'
            ]);

            // Update lokasi di KIR
            $existingKir->update(['ruangan_id' => $this->ruangan->id]);
            session()->flash('success', "Mutasi berhasil! Aset dipindahkan ke ruangan ini.");
            
        } else {
            // Jika ini pertama kali barang ditempatkan (Dari Gudang Induk)
            Kir::create([
                'ruangan_id' => $this->ruangan->id,
                'barang_id' => $barang->id,
                'kondisi' => 'Baik', // Default pertama kali
            ]);
            
            MutasiBarang::create([
                'barang_id' => $barang->id,
                // ruangan_asal_id dibuat null/kosongkan jika database mengizinkan, atau buat ruangan dummy 'Gudang Pusat'
                'ruangan_tujuan_id' => $this->ruangan->id,
                'user_id' => $userId,
                'tanggal_mutasi' => now(),
                'alasan_mutasi' => 'Penempatan Awal Aset'
            ]);

            session()->flash('success', "Aset baru berhasil di-Check-In ke ruangan ini.");
        }

        $this->scanKode = '';
    }

    // ----------------------------------------------------
    // FITUR 2: UPDATE KONDISI (OTOMATIS CATAT HISTORI)
    // ----------------------------------------------------
    public function bukaModalKondisi($kirId)
    {
        $this->reset(['foto_bukti', 'keterangan_audit']);
        $kir = Kir::findOrFail($kirId);
        $this->selectedKirId = $kirId;
        $this->kondisi_baru = $kir->kondisi;
        $this->isModalKondisiOpen = true;
    }

    public function simpanKondisi()
    {
        $this->validate([
            'kondisi_baru' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'foto_bukti' => 'nullable|image|max:2048', // Opsional, tapi disarankan
        ]);

        $kir = Kir::findOrFail($this->selectedKirId);
        $kondisiLama = $kir->kondisi;

        $path = $this->foto_bukti ? $this->foto_bukti->store('audit-kondisi', 'public') : null;

        // 1. CATAT KE HISTORI KONDISI
        KondisiHistory::create([
            'kir_id' => $kir->id,
            'kondisi_lama' => $kondisiLama,
            'kondisi_baru' => $this->kondisi_baru,
            'foto_bukti' => $path,
            'keterangan' => $this->keterangan_audit,
        ]);

        // 2. UPDATE STATUS TERKINI DI KIR
        $kir->update([
            'kondisi' => $this->kondisi_baru,
            // Jika ada foto baru, update foto lokasi. Jika tidak, pertahankan yang lama
            'foto_kondisi_lokasi' => $path ? $path : $kir->foto_kondisi_lokasi 
        ]);

        $this->isModalKondisiOpen = false;
        session()->flash('success', "Kondisi aset berhasil diaudit & histori dicatat.");
    }

    public function with(): array
    {
        return [
            // Mengambil daftar barang yang saat ini tercatat di KIR ruangan ini
            'daftarKir' => Kir::with(['barang.sourceable', 'barang.brand', 'barang.tipe'])
                            ->where('ruangan_id', $this->ruangan->id)
                            ->latest()->get()
        ];
    }
}; ?>

<div class="max-w-md mx-auto space-y-6 pb-20">
    
    @if(session('success'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-2xl text-sm font-bold border border-emerald-100 shadow-sm flex gap-2 items-center" x-data="{show: true}" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 text-rose-700 rounded-2xl text-sm font-bold border border-rose-100 shadow-sm flex gap-2 items-center" x-data="{show: true}" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-[2rem] p-6 shadow-xl relative overflow-hidden text-white border border-indigo-500">
        <svg class="absolute -top-10 -right-10 w-48 h-48 text-white/10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg>
        <div class="relative z-10">
            <span class="px-3 py-1 bg-white/20 backdrop-blur rounded-lg text-[10px] font-black uppercase tracking-widest">{{ $ruangan->kode_ruangan }}</span>
            <h1 class="text-3xl font-black mt-3 mb-1 leading-tight">{{ $ruangan->nama }}</h1>
            <p class="text-indigo-200 text-xs font-medium">PIC: {{ optional($ruangan->penanggungJawab)->nama ?? 'Belum Ada PIC' }}</p>
            
            <div class="mt-6 pt-4 border-t border-indigo-400/30 flex justify-between items-center">
                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Total Aset (KIR)</span>
                <span class="text-2xl font-black">{{ $daftarKir->count() }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] p-5 shadow-sm border border-slate-200">
        <label class="block text-sm font-black text-slate-800 mb-2">Scan Aset Masuk</label>
        <form wire:submit="checkInBarang" class="flex gap-2">
            <input wire:model="scanKode" type="text" autofocus class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 font-mono font-bold text-indigo-600 focus:border-indigo-500 focus:ring-2 placeholder:text-slate-400 text-sm" placeholder="Arahkan scanner ke QR...">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 rounded-2xl font-bold transition-all shadow-md">Masuk</button>
        </form>
    </div>

    <div>
        <h3 class="font-black text-slate-800 text-lg mb-4 px-2">Daftar Aset Fisik</h3>
        
        <div class="space-y-4">
            @forelse($daftarKir as $kir)
                <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-200 flex flex-col gap-3">
                    
                    <div class="flex gap-4 items-start">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 shrink-0 border border-slate-200 overflow-hidden relative">
                            @if($kir->foto_kondisi_lokasi)
                                <img src="{{ asset('storage/' . $kir->foto_kondisi_lokasi) }}" class="w-full h-full object-cover">
                                <div class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-[8px] text-center py-0.5 font-bold">TERKINI</div>
                            @elseif($kir->barang->foto_barang)
                                <img src="{{ asset('storage/' . $kir->barang->foto_barang) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-300">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg>
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex-1">
                            <div class="text-[10px] font-bold text-indigo-500 font-mono tracking-widest mb-1">{{ $kir->barang->kode_inventaris }}</div>
                            <h4 class="font-bold text-slate-800 text-sm leading-tight mb-1.5">{{ $kir->barang->sourceable->asset_name ?? $kir->barang->sourceable->material_description ?? 'Aset' }}</h4>
                            
                            @php
                                $color = match($kir->kondisi) {
                                    'Baik' => 'bg-emerald-100 text-emerald-700',
                                    'Rusak Ringan' => 'bg-amber-100 text-amber-700',
                                    'Rusak Berat' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-slate-100 text-slate-700'
                                };
                            @endphp
                            <span class="px-2 py-1 rounded-lg text-[9px] font-black uppercase {{ $color }} inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full {{ str_replace('100', '500', $color) }}"></span>
                                {{ $kir->kondisi }}
                            </span>
                        </div>
                    </div>

                    <button wire:click="bukaModalKondisi({{ $kir->id }})" class="w-full py-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Audit / Lapor Kerusakan
                    </button>
                </div>
            @empty
                <div class="bg-white border-2 border-dashed border-slate-200 rounded-3xl p-8 text-center">
                    <p class="font-bold text-slate-600">Belum Ada Aset Terdata</p>
                    <p class="text-xs text-slate-400 mt-1">Gunakan scanner QR untuk mendata aset ke ruangan ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($isModalKondisiOpen)
    <div class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-t-[2rem] sm:rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between bg-slate-50 items-center">
                <h3 class="font-bold text-lg text-slate-800">Laporan Kondisi Aset</h3>
                <button wire:click="$set('isModalKondisiOpen', false)" class="text-slate-400 hover:text-rose-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <form wire:submit="simpanKondisi" class="space-y-5">
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Kondisi Fisik Terkini</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="kondisi_baru" value="Baik" class="peer sr-only">
                                <div class="px-2 py-3 border-2 border-slate-100 rounded-xl text-center text-xs font-bold text-slate-500 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 transition-all">Baik</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="kondisi_baru" value="Rusak Ringan" class="peer sr-only">
                                <div class="px-2 py-3 border-2 border-slate-100 rounded-xl text-center text-xs font-bold text-slate-500 peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 transition-all">Rusak Ringan</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="kondisi_baru" value="Rusak Berat" class="peer sr-only">
                                <div class="px-2 py-3 border-2 border-slate-100 rounded-xl text-center text-xs font-bold text-slate-500 peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 transition-all">Rusak Berat</div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Foto Bukti (Gunakan Kamera)</label>
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-200 rounded-2xl cursor-pointer bg-slate-50 relative overflow-hidden">
                            @if($foto_bukti)
                                <img src="{{ $foto_bukti->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-8 h-8 mb-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    <span class="text-xs font-bold text-slate-500">Ambil Foto (Opsional)</span>
                                </div>
                            @endif
                            <input type="file" wire:model="foto_bukti" accept="image/*" capture="environment" class="hidden" />
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Catatan Kerusakan</label>
                        <textarea wire:model="keterangan_audit" rows="2" class="w-full rounded-xl border-slate-200 focus:ring-indigo-500 text-sm" placeholder="Misal: Layar retak di ujung kiri..."></textarea>
                    </div>

                    <div class="pt-4 mt-2 border-t border-slate-100">
                        <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-lg transition-all">Simpan Laporan & Histori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>