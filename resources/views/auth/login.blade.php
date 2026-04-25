<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50 relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-full z-0">
            <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] rounded-full bg-blue-100/50 blur-3xl"></div>
            <div class="absolute -bottom-[10%] -left-[10%] w-[30%] h-[30%] rounded-full bg-indigo-100/50 blur-3xl"></div>
        </div>

        <div class="z-10 w-full sm:max-w-md mt-6 px-8 py-10 bg-white/70 backdrop-blur-lg shadow-2xl border border-white/50 overflow-hidden sm:rounded-[2.5rem]">
            
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl shadow-lg shadow-blue-200 mb-4">
                    <span class="text-white font-bold text-3xl">D</span>
                </div>
                <h2 class="text-2xl font-bold text-slate-800">Selamat Datang Kembali</h2>
                <p class="text-slate-500 text-sm mt-2">Silakan masuk ke akun D-KIR Anda</p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="user" class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </span>
                        <input id="user" 
                               class="block w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                               name="user" 
                               :value="old('user')" 
                               required 
                               autofocus 
                               autocomplete="username" 
                               placeholder="Masukkan Username" />
                    </div>
                    <x-input-error :messages="$errors->get('user')" class="mt-2" />
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                        @if (Route::has('password.request'))
                            <a class="text-xs font-semibold text-blue-600 hover:text-blue-700" href="{{ route('password.request') }}">
                                Lupa Password?
                            </a>
                        @endif
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        <input id="password" class="block w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                    <label for="remember_me" class="ml-2 text-sm text-slate-600">Ingat perangkat ini</label>
                </div>

                <div>
                    <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Masuk ke Sistem
                    </button>
                </div>
            </form>

            @if (Route::has('register'))
                <p class="text-center mt-8 text-sm text-slate-500">
                    Belum punya akses? <a href="{{ route('register') }}" class="font-bold text-blue-600 hover:underline">Hubungi Admin IT</a>
                </p>
            @endif
        </div>
        
        <p class="z-10 mt-8 text-slate-400 text-xs">
            &copy; {{ date('Y') }} AirNav Indonesia - Cabang Surabaya
        </p>
    </div>
</x-guest-layout>