<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Grant all abilities to administrators
        Gate::before(function (?User $user, $ability) {
            if (! $user) {
                return null;
            }
            return $user->isAdmin() ? true : null;
        });

        // Explicit gate for manage-plans (redundant with Gate::before but explicit)
        Gate::define('manage-plans', function (User $user) {
            return $user->isAdmin();
        });
    }
}