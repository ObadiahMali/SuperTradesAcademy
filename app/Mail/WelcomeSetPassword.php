<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeSetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        $resetUrl = route('password.reset', ['token' => $this->token, 'email' => $this->user->email]);

        return $this->subject('Welcome to SuperTrades Academy — Set your password')
                    ->view('emails.welcome-set-password')
                    ->with([
                        'user' => $this->user,
                        'resetUrl' => $resetUrl,
                        'expiryMinutes' => config('auth.passwords.users.expire', 60),
                    ]);
    }
}
