<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeSetPassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register'); // your register view
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            // add other fields as needed
        ]);

        // Create user with a random password so they must set via email
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(40)),
        ]);

        // Create a password reset token for the user
        $token = Password::broker()->createToken($user);

        // Send branded welcome email with set-password link
        Mail::to($user->email)->send(new WelcomeSetPassword($user, $token));

        // Redirect to login with a friendly message
        return redirect()->route('login')->with('status', 'Registration successful. Check your email to set your password.');
    }
}
