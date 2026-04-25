<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>D-KIR | AirNav Indonesia</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-slate-50">
    <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-blue-500 selection:text-white">
        
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-blue-100/50 blur-3xl"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[30%] h-[30%] rounded-full bg-indigo-100/50 blur-3xl"></div>
        </div>

        <nav class="fixed top-0 w-full p-6 flex justify-between items-center z-50">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-200">
                    <span class="text-white font-bold text-xl">D</span>
                </div>
                <span class="text-xl font-bold text-slate-800 tracking-tight">Digital <span class="text-blue-600">KIR</span></span>
            </div>
            
            @if (Route::has('login'))
                <div class="space-x-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-semibold text-slate-600 hover:text-blue-600 transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-full font-semibold text-blue-600 bg-white border border-blue-100 shadow-sm hover:bg-blue-50 transition">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-full font-semibold text-white bg-blue-600 shadow-md shadow-blue-200 hover:bg-blue-700 transition">Get Started</a>
                        @endif
                    @endauth
                </div>
            @endif
        </nav>

        <main class="relative z-10 max-w-7xl mx-auto px-6 pt-20 flex flex-col items-center text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-sm font-medium mb-6 animate-bounce">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                Sistem Inventaris Real-time
            </div>
            
            <h1 class="text-5xl md:text-7xl font-extrabold text-slate-900 mb-6 leading-tight">
                Kelola Aset Ruangan <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-500">
                    Lebih Cerdas & Akurat.
                </span>
            </h1>
            
            <p class="text-lg text-slate-600 max-w-2xl mb-10 leading-relaxed">
                Platform digital untuk pemantauan Kartu Inventaris Ruangan (KIR). Terintegrasi dengan data SAP, pelacakan mutasi barang, dan audit fisik berbasis QR Code.
            </p>

            <div class="flex flex-col sm:flex-row gap-4">
                <a href="{{ route('login') }}" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold text-lg shadow-xl shadow-blue-200 hover:bg-blue-700 hover:-translate-y-1 transition-all duration-300">
                    Mulai Inventarisasi
                </a>
                <a href="#features" class="px-8 py-4 bg-white text-slate-700 border border-slate-200 rounded-2xl font-bold text-lg hover:bg-slate-50 transition-all duration-300">
                    Pelajari Fitur
                </a>
            </div>

            <div class="mt-20 relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-[2rem] blur opacity-20 group-hover:opacity-30 transition duration-1000"></div>
                <div class="relative bg-white border border-slate-200 rounded-[2rem] shadow-2xl overflow-hidden p-2">
                    <div class="bg-slate-50 rounded-[1.5rem] p-4 flex gap-4 items-center border border-slate-100">
                         <div class="w-1/3 h-40 bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex flex-col justify-between">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <div>
                                <div class="h-2 w-12 bg-slate-200 rounded mb-2"></div>
                                <div class="h-4 w-20 bg-slate-800 rounded"></div>
                            </div>
                         </div>
                         <div class="w-2/3 h-40 bg-white rounded-xl shadow-sm border border-slate-100 p-4">
                            <div class="flex gap-2 mb-4">
                                <div class="h-2 w-full bg-blue-100 rounded"></div>
                                <div class="h-2 w-1/2 bg-slate-100 rounded"></div>
                            </div>
                            <div class="space-y-3">
                                <div class="h-3 w-full bg-slate-50 rounded"></div>
                                <div class="h-3 w-full bg-slate-50 rounded"></div>
                                <div class="h-3 w-3/4 bg-slate-50 rounded"></div>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="mt-20 pb-10 text-slate-400 text-sm">
            &copy; {{ date('Y') }} AirNav Indonesia - Cabang Surabaya. All rights reserved.
        </footer>
    </div>
</body>
</html>