<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | D-KIR AirNav</title>
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    </head>
<body class="font-sans antialiased bg-slate-50 text-slate-900" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">
        
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false" 
             class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
               class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-slate-200 transition-transform duration-300 transform lg:translate-x-0 lg:static lg:inset-0">
            
            <div class="flex items-center justify-center h-20 border-b border-slate-100 px-6">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3 shadow-lg shadow-blue-200">
                    <span class="text-white font-bold text-lg">D</span>
                </div>
                <span class="text-xl font-bold tracking-tight">Dashboard <span class="text-blue-600 italic">D-KIR</span></span>
            </div>

            <nav class="mt-6 px-4 space-y-2 overflow-y-auto">
                <a href="/dashboard" class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all {{ request()->is('dashboard') ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-slate-600 hover:bg-slate-50' }}">
                    Dashboard
                </a>
                
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Manajemen Lokasi</div>
                <a href="{{ route('lokasi.index') }}" 
                class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all {{ request()->routeIs('lokasi.index') ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-slate-600 hover:bg-slate-50' }}">
                    Cabang & Gedung
                </a>

                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Manajemen SDM</div>
                <a href="{{ route('sdm.index') }}" 
                class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all {{ request()->routeIs('sdm.index') ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-slate-600 hover:bg-slate-50' }}">
                    Jabatan, Penanggung Jawab
                </a>
                
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Inventaris & SAP</div>
                <a href="{{ route('sap.asset.import') }}" 
                class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all {{ request()->routeIs('sap.asset.import') ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-slate-600 hover:bg-slate-50' }}">
                    Import Data Asset SAP
                </a>
                <a href="{{ route('sap.kcl.import') }}" 
                class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all {{ request()->routeIs('sap.kcl.import') ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-slate-600 hover:bg-slate-50' }}">
                    Import Data KCL SAP
                </a>
                <a href="{{ route('katalog.index') }}" 
                class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all {{ request()->routeIs('katalog.index') ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-slate-600 hover:bg-slate-50' }}">
                    Katalog Barang
                </a>
                <a href="{{ route('barang.index') }}" 
                class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all {{ request()->routeIs('barang.index') ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-slate-600 hover:bg-slate-50' }}">
                    Manajemen Barang
                </a>
                
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Manajemen Barang</div>
                <a href="{{ route('data.kir') }}" 
                class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all {{ request()->routeIs('data.kir') ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'text-slate-600 hover:bg-slate-50' }}">
                    KIR
                </a>


                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Sistem</div>
                <a href="#" class="flex items-center px-4 py-3 text-sm font-semibold text-slate-600 rounded-xl hover:bg-slate-50 transition-all">
                    Manajemen User
                </a>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="flex items-center justify-between h-20 bg-white border-b border-slate-200 px-6">
                <button @click="sidebarOpen = true" class="p-2 rounded-lg text-slate-500 hover:bg-slate-50 lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                
                <div class="flex-1 px-4 hidden md:block text-sm text-slate-500 font-medium uppercase tracking-widest">
                    {{ now()->translatedFormat('l, d F Y') }}
                </div>

                <div class="flex items-center gap-4" x-data="{ open: false }">
                    @auth
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-slate-800">{{ Auth::user()->nama }}</p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">{{ Auth::user()->role }}</p>
                    </div>
                    @endauth
                    <div class="relative">
                        <button @click="open = !open" class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center border-2 border-white shadow-sm overflow-hidden focus:outline-none">
                            @auth
                            <img src="https://ui-avatars.com/api/?name={{ Auth::user()->nama }}&background=0D8ABC&color=fff" alt="avatar">
                            @endauth    
                        </button>
                        
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             @click.away="open = false" 
                             class="absolute right-0 mt-3 w-48 bg-white border border-slate-200 rounded-2xl shadow-xl py-2 z-50 overflow-hidden">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-5 py-3 text-sm font-bold text-red-600 hover:bg-red-50 transition">
                                    Keluar Sistem
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6 md:p-10">
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>