<x-app-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen SAP Assets</h2>
        <p class="text-slate-500">Import dan kelola database nilai aset secara real-time.</p>
    </div>

    <div class="grid grid-cols-1 gap-8">
        <livewire:sap-asset-import-form />

        <div class="mt-4">
            <h3 class="text-xl font-bold text-slate-800 mb-4 px-2">Daftar Aset Terdaftar</h3>
            
            <livewire:sap-asset-table />
        </div>
    </div>
</x-app-layout>