<?php

namespace App\Providers;

use App\Models\User; // Import Model User
use Illuminate\Support\Facades\Gate; // Import Facade Gate
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Gate::define('access-admin', function (User $user) {
            return $user->role === 'admin_pusat'; 
        });
    }
}
