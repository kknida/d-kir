<x-app-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen SDM & Penanggung Jawab</h2>
        <p class="text-slate-500">Kelola master data Jabatan dan daftar Pegawai yang bertanggung jawab atas aset/ruangan.</p>
    </div>

    <div x-data="{ activeTab: 'jabatan' }" class="space-y-6">
        
        <div class="bg-white p-2 rounded-2xl border border-slate-200 shadow-sm inline-flex flex-wrap gap-2">
            <button @click="activeTab = 'jabatan'" class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all" :class="activeTab === 'jabatan' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-100'">
                1. Master Jabatan
            </button>
            <button @click="activeTab = 'pj'" class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all" :class="activeTab === 'pj' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-100'">
                2. Data Penanggung Jawab
            </button>
        </div>

        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 min-h-[500px]">
            <div x-show="activeTab === 'jabatan'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="mb-6 border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-bold text-slate-800">Daftar Jabatan</h3>
                    <p class="text-sm text-slate-500">Struktur jabatan struktural maupun fungsional.</p>
                </div>
                <livewire:sdm.jabatan-table />
            </div>

            <div x-show="activeTab === 'pj'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" x-cloak>
                <div class="mb-6 border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-bold text-slate-800">Penanggung Jawab Aset</h3>
                    <p class="text-sm text-slate-500">Daftar personil lengkap dengan NIP dan jabatannya.</p>
                </div>
                <livewire:sdm.pj-table />
            </div>
        </div>
    </div>
</x-app-layout>