<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
     * Check role by string (single role stored in `role` column).
     */
    public function hasRole(string $role): bool
    {
        return Str::lower($this->role ?? '') === Str::lower($role);
    }

    /**
     * Convenience: is administrator.
     */
  

    /**
     * Compatibility: assign a role name.
     * Accepts a string or an array (uses the first value for arrays).
     * Stores the single role in the `role` column and saves the model.
     */
    public function assignRole(string|array $roles): static
    {
        $role = is_array($roles) ? (string) (array_values($roles)[0] ?? '') : (string) $roles;
        $this->role = $role !== '' ? $role : null;
        $this->save();

        return $this;
    }

    /**
     * Compatibility: sync roles (store first role).
     */
    public function syncRoles(string|array $roles): static
    {
        return $this->assignRole($roles);
    }

    /**
     * Compatibility: return role names as a Collection (like Spatie's getRoleNames()).
     */
//   use Illuminate\Support\Collection;

    /**
     * Compatibility: return role names as a Collection (like Spatie's getRoleNames()).
     */
    public function getRoleNames(): Collection
    {
        return collect($this->role ? [(string) $this->role] : []);
    }

}
