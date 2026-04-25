<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Tentukan aturan validasi.
     */
    public function rules(): array
    {
        return [
            'user' => ['required', 'string'], // Ubah 'email' menjadi 'user'
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Coba lakukan autentikasi.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Gunakan field 'user' dan sesuaikan dengan kolom di database (misal kolomnya bernama 'user' atau 'username')
        if (! Auth::attempt($this->only('user', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'user' => trans('auth.failed'), // Pesan error muncul di input 'user'
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Pastikan request login tidak dibatasi (Rate Limiting).
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'user' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Ambil throttle key untuk rate limiting.
     */
    public function throttleKey(): string
    {
        // Gunakan field 'user' sebagai kunci pembatas login
        return Str::transliterate(Str::lower($this->input('user')).'|'.$this->ip());
    }
}