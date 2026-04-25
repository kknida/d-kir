<x-app-layout>
    <div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Portal Data KIR</h2>
            <p class="text-slate-500 mt-1">Pilih ruangan untuk mengelola daftar inventaris fisik, mutasi, dan check-in aset.</p>
        </div>
    </div>

    <livewire:inventaris.data-kir-list />

</x-app-layout>