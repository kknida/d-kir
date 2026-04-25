<x-app-layout>
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('data.kir') }}" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="text-2xl font-black text-indigo-900 tracking-tight">KIR: {{ $ruangan->nama }}</h2>
        </div>
        <p class="text-slate-500 pl-11">
            Gedung: <span class="font-bold text-slate-700">{{ optional($ruangan->lantai->gedung)->nama }}</span> | 
            PIC: <span class="font-bold text-indigo-600">{{ optional($ruangan->penanggungJawab)->nama ?? 'Belum ada PIC' }}</span>
        </p>
    </div>

    <livewire:inventaris.kir-detail-table :ruangan="$ruangan" />

</x-app-layout>