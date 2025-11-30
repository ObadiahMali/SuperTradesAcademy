<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    

public function boot(): void
{
    // Define custom gates here if you want,
    // but do NOT call registerPolicies() in AppServiceProvider.
    Gate::define('manage-plans', function ($user) {
        return $user->hasRole('administrator'); // or your own logic
    });
}

}
