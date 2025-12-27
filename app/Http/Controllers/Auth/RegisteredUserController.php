<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    /**
     * Show registration view (Breeze default).
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request)
    {
        // Basic validation for public registration
        $baseRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ];

        // Determine if current user is allowed to manage users (admin)
        $isAdmin = auth()->check() && auth()->user()->can('manage-users');

        // If admin is creating a user, allow optional role and optional password/send_invite
        if ($isAdmin) {
            $adminRules = [
                'role' => ['nullable', 'string', 'in:secretary,administrator'],
                'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
                'send_invite' => ['nullable', 'boolean'],
            ];

            $request->validate(array_merge($baseRules, $adminRules));
        } else {
            // Public registration requires a password
            $publicRules = [
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ];

            $request->validate(array_merge($baseRules, $publicRules));
        }

        // Prepare attributes for user creation
        $attributes = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        // Handle password:
        // - Public users must provide a password (validated above)
        // - Admins may provide a password or leave blank; if blank we generate one
        $generatedPassword = null;
        if ($isAdmin) {
            if ($request->filled('password')) {
                $attributes['password'] = Hash::make($request->password);
            } else {
                // generate a secure random password for admin-created users when none provided
                $generatedPassword = Str::random(24);
                $attributes['password'] = Hash::make($generatedPassword);
            }

            // set role if provided (admins only)
            if ($request->filled('role')) {
                $attributes['role'] = $request->input('role');
            } else {
                // keep role null if admin didn't choose one
                $attributes['role'] = null;
            }
        } else {
            // public registration: password is required and validated
            $attributes['password'] = Hash::make($request->password);
            // do not set role for public registration (leave null)
        }

        // Create the user
        $user = User::create($attributes);

        // If Spatie is installed or User model provides assignRole, attempt to assign role (admins only)
        if ($isAdmin && $user && $user->role) {
            try {
                if (class_exists(\Spatie\Permission\Models\Role::class)) {
                    \Spatie\Permission\Models\Role::firstOrCreate(['name' => $user->role]);
                }

                if (method_exists($user, 'assignRole')) {
                    $user->assignRole($user->role);
                }
            } catch (\Throwable $e) {
                Log::warning('Role assignment failed: ' . $e->getMessage());
            }
        }

        // If admin requested to send an invite (password reset) or we generated a password,
        // send a password reset link so the user can set their own password.
        if ($isAdmin) {
            $sendInvite = (bool) $request->input('send_invite', false);

            // If admin left password blank, prefer sending a reset link so the user sets their password.
            if ($generatedPassword !== null) {
                $sendInvite = true;
            }

            if ($sendInvite) {
                try {
                    Password::sendResetLink(['email' => $user->email]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send password reset link: ' . $e->getMessage());
                }
            }
        }

        // Fire the Registered event for any listeners (email verification, etc.)
        event(new Registered($user));

        // If created by an admin, do not auto-login; redirect back with a status message.
        if ($isAdmin) {
            return redirect()->back()->with('status', 'User created successfully.');
        }

        // For public registration, log the user in and redirect to dashboard
        auth()->login($user);

        return redirect()->intended(route('dashboard'));
    }
}
