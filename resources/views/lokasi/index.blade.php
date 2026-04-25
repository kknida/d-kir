<x-app-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Lokasi & Fasilitas</h2>
        <p class="text-slate-500">Kelola hierarki Cabang, Gedung, Lantai, hingga Ruangan spesifik.</p>
    </div>

    <div x-data="{ activeTab: 'cabang' }" class="space-y-6">
        
        <div class="bg-white p-2 rounded-2xl border border-slate-200 shadow-sm inline-flex flex-wrap gap-2">
            <button @click="activeTab = 'cabang'" class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all" :class="activeTab === 'cabang' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-100'">
                1. Cabang Kantor
            </button>
            <button @click="activeTab = 'gedung'" class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all" :class="activeTab === 'gedung' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-100'">
                2. Data Gedung
            </button>
            <button @click="activeTab = 'lantai'" class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all" :class="activeTab === 'lantai' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-100'">
                3. Tingkat / Lantai
            </button>
            <button @click="activeTab = 'ruangan'" class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all" :class="activeTab === 'ruangan' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-100'">
                4. Ruangan (QR)
            </button>
            
        </div>

        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 min-h-[500px]">
            
            <div x-show="activeTab === 'cabang'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <div class="mb-6 border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-bold text-slate-800">Daftar Cabang</h3>
                    <p class="text-sm text-slate-500">Level tertinggi dalam hierarki lokasi (Contoh: Kantor Pusat, Cabang Surabaya).</p>
                </div>
                <livewire:lokasi.cabang-table />
            </div>

            <div x-show="activeTab === 'gedung'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" x-cloak>
                <div class="mb-6 border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-bold text-slate-800">Manajemen Gedung</h3>
                    <p class="text-sm text-slate-500">Bangunan fisik yang berada di dalam suatu Cabang.</p>
                </div>
                <livewire:lokasi.gedung-table />
            </div>

            <div x-show="activeTab === 'lantai'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" x-cloak>
                <div class="mb-6 border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-bold text-slate-800">Data Lantai</h3>
                    <p class="text-sm text-slate-500">Tingkatan lantai pada masing-masing gedung.</p>
                </div>
                <livewire:lokasi.lantai-table />
            </div>

            <div x-show="activeTab === 'ruangan'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" x-cloak>
                <div class="mb-6 border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-bold text-slate-800">Registrasi Ruangan</h3>
                    <p class="text-sm text-slate-500">Titik lokasi spesifik penempatan aset yang akan menghasilkan QR Code.</p>
                </div>
                <livewire:lokasi.ruangan-table />
            </div>

        </div>
    </div>
</x-app-layout>