<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Inventaris - {{ $barang->kode_inventaris }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 pb-12">
    
    <div class="w-full h-64 bg-slate-800 relative">
        @if($barang->foto_barang)
            <img src="{{ asset('storage/' . $barang->foto_barang) }}" class="w-full h-full object-cover opacity-80">
        @else
            <div class="w-full h-full flex items-center justify-center text-slate-600">No Image Available</div>
        @endif
        
        <div class="absolute bottom-0 inset-x-0 p-6 bg-gradient-to-t from-slate-900 to-transparent pt-20">
            <div class="flex gap-2 mb-2">
                <span class="px-2.5 py-1 bg-white/20 backdrop-blur text-white rounded-lg text-xs font-bold uppercase tracking-wider">{{ $barang->brand->nama }}</span>
                <span class="px-2.5 py-1 bg-blue-500/80 backdrop-blur text-white rounded-lg text-xs font-bold uppercase tracking-wider">{{ $barang->tipe->nama }}</span>
            </div>
            <h1 class="text-2xl font-bold text-white leading-tight">
                {{ $barang->sourceable->asset_name ?? $barang->sourceable->material_description ?? 'Data Tidak Ditemukan' }}
            </h1>
        </div>
    </div>

    <div class="px-4 -mt-4 relative z-10">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
            
            <div class="flex justify-between items-center pb-4 border-b border-slate-100 mb-4">
                <div>
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mb-1">Kode Inventaris</p>
                    <p class="text-lg font-mono font-bold text-slate-800">{{ $barang->kode_inventaris }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-widest mb-1">Status</p>
                    <p class="text-sm font-bold text-emerald-600">Terdaftar</p>
                </div>
            </div>

            <div class="mb-6 p-4 bg-blue-50 rounded-2xl border border-blue-100">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <div>
                        <p class="text-xs text-blue-800 uppercase font-bold mb-0.5">Lokasi Sistem (SAP)</p>
                        <p class="text-sm font-bold text-blue-900">
                            {{ $barang->sourceable->asset_location ?? $barang->sourceable->plant ?? 'Lokasi Belum Ditentukan' }}
                        </p>
                        <p class="text-xs text-blue-600 mt-1">*Posisi aktual akan diperbarui saat KIR</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <p class="text-xs text-slate-500 font-bold mb-1">Keterangan Fisik</p>
                    <p class="text-sm text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-100">{{ $barang->keterangan ?: 'Tidak ada catatan khusus.' }}</p>
                </div>

                @if($barang->sourceable_type == 'App\Models\SapAsset')
                    <div>
                        <p class="text-xs text-slate-500 font-bold mb-1">Nilai Buku / Acquis. Val (Sistem)</p>
                        <p class="text-sm text-slate-700 font-medium">Rp {{ number_format($barang->sourceable->book_val, 0, ',', '.') }} / Rp {{ number_format($barang->sourceable->acquis_val, 0, ',', '.') }}</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</body>
</html>