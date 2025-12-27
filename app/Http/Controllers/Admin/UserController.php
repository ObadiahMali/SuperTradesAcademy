<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Mail\WelcomeSetPassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    public function __construct()
    {
        // Require authentication for all actions in this controller
        $this->middleware('auth');
    }

    /**
     * Show a paginated list of users.
     */
    public function index(Request $request)
    {
        $query = User::query()->latest('created_at');

        if ($q = trim($request->query('q', ''))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('role', 'like', "%{$q}%");
            });
        }

        $users = $query->paginate(25);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form to create a new user.
     */
    public function create()
    {
        $roles = [
            'administrator' => 'Administrator',
            'secretary' => 'Secretary',
        ];

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
   public function store(StoreAdminUserRequest $request)
{
    $data = $request->validated();

    // If admin didn't provide a password, generate a secure one
    $passwordPlain = !empty($data['password']) ? $data['password'] : Str::random(12);
    $passwordHash  = Hash::make($passwordPlain);
    $role          = $data['role'] ?? 'secretary';

    DB::beginTransaction();

    try {
        // Create and persist user
        $user = new User();
        $user->name     = $data['name'];
        $user->email    = $data['email'];
        $user->password = $passwordHash;
        $user->role     = $role;
        $user->save();

        // Always send a branded password set (invite) email
        try {
            // Create a password reset token using the broker
            $token = Password::broker()->createToken($user);

            // Send the branded mailable with the token (uses the password.reset route)
            Mail::to($user->email)->send(new WelcomeSetPassword($user, $token));

            Log::info("Invite email sent to user_id={$user->id}, email={$user->email}");
        } catch (\Throwable $e) {
            // Log but don't fail the whole transaction for email issues
            Log::warning("Password invite failed for user_id={$user->id}: " . $e->getMessage());
        }

        DB::commit();

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Failed to create user: ' . $e->getMessage());
        return redirect()->back()->withInput()->with('error', 'Failed to create user.');
    }
}


    /**
     * Show a single user.
     */
    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form to edit a user.
     */
    public function edit(User $user)
    {
        $roles = [
            'administrator' => 'Administrator',
            'secretary' => 'Secretary',
        ];

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update an existing user.
     */
    public function update(StoreAdminUserRequest $request, User $user)
    {
        $data = $request->validated();

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'] ?? $user->role;

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {
        $currentUserId = auth()->id();

        if ($currentUserId && $currentUserId === $user->id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        try {
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'User deleted.');
        } catch (\Throwable $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete user. Please check logs.');
        }
    }
}
