<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function __construct()
    {
        // Ensure only authorized admins can manage users
        $this->middleware(['auth', 'can:manage-users']);
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
            'staff' => 'Staff',
        ];

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreAdminUserRequest $request)
    {
        $data = $request->validated();

        // Generate a secure password if none provided
        $passwordPlain = $data['password'] ?? Str::random(12);
        $passwordHash = Hash::make($passwordPlain);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $passwordHash,
            'role' => $data['role'] ?? 'staff',
            'is_active' => $data['is_active'] ?? true,
        ]);

        // Optionally send a welcome email with credentials if requested
        if (!empty($data['send_invite'])) {
            try {
                // Implement App\Mail\AdminCreatedUser mailable if you want to send credentials
                Mail::to($user->email)->send(new \App\Mail\AdminCreatedUser($user, $passwordPlain));
            } catch (\Throwable $e) {
                // Log or ignore; do not expose mail errors to the user
                \Log::warning('Failed to send admin created email: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
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
            'staff' => 'Staff',
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
        $user->is_active = $data['is_active'] ?? $user->is_active;

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
        // Prevent deleting self
        if ($this->request()->user()->id === $user->id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    /**
     * Helper to access the current request inside controller methods where needed.
     */
    protected function request(): Request
    {
        return app(Request::class);
    }
}