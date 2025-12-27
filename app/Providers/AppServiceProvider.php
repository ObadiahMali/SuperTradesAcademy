<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Check if user has the given role name.
     */
    public function hasRole(string $role): bool
    {
        return strtolower($this->role ?? '') === strtolower($role);
    }

    /**
     * Convenience: is administrator.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('administrator');
    }
}