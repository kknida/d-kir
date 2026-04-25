<x-app-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Import Data SAP KCL</h2>
        <p class="text-slate-500">Perbarui database stok dan finansial menggunakan file Excel.</p>
    </div>

    <div class="grid grid-cols-1 gap-8">
        <livewire:sap-kcl-import-form />

        <div class="mt-4">
            <h3 class="text-xl font-bold text-slate-800 mb-4 px-2">Data SAP Terbaru</h3>
            <livewire:sap-kcl-table />
        </div>
    </div>
</x-app-layout>