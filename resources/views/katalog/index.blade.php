<x-app-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Katalog Master Barang</h2>
        <p class="text-slate-500">Kelola master data Kategori, Tipe, dan Brand untuk aset dan inventaris.</p>
    </div>

    <div x-data="{ activeTab: 'kategori' }" class="space-y-6">
        
        <div class="bg-white p-2 rounded-2xl border border-slate-200 shadow-sm inline-flex flex-wrap gap-2">
            <button @click="activeTab = 'kategori'" 
                    class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all"
                    :class="activeTab === 'kategori' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700'">
                Kategori Asset
            </button>
            
            <button @click="activeTab = 'tipe'" 
                    class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all"
                    :class="activeTab === 'tipe' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700'">
                Tipe & Varian
            </button>
            
            <button @click="activeTab = 'brand'" 
                    class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all"
                    :class="activeTab === 'brand' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700'">
                Merk / Brand
            </button>
        </div>

        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 min-h-[500px]">
            
            <div x-show="activeTab === 'kategori'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 style="display: none;">
                
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-slate-800">Manajemen Kategori</h3>
                    <p class="text-sm text-slate-500">Kelompok utama untuk mengklasifikasikan aset dan barang inventaris.</p>
                </div>
                
                <livewire:katalog.kategori-table />
                
            </div>

            <div x-show="activeTab === 'tipe'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 style="display: none;" 
                 x-cloak>
                
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-slate-800">Manajemen Tipe Barang</h3>
                    <p class="text-sm text-slate-500">Sub-kelompok spesifik dari setiap Kategori yang ada.</p>
                </div>

                <livewire:katalog.tipe-table />

            </div>

            <div x-show="activeTab === 'brand'" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-2" 
                 x-transition:enter-end="opacity-100 translate-y-0" 
                 style="display: none;" 
                 x-cloak>
                
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-slate-800">Manajemen Merk / Brand</h3>
                    <p class="text-sm text-slate-500">Daftar merk atau pabrikan dari barang-barang inventaris.</p>
                </div>
                
                <livewire:katalog.brand-table />
                
            </div>

        </div>
    </div>
</x-app-layout>