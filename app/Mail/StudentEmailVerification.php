<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentEmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $token;

    /**
     * Create a new message instance.
     *
     * @param  mixed  $student
     * @param  string  $token
     */
    public function __construct($student, string $token)
    {
        $this->student = $student;
        $this->token = $token;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Verify your email for SuperTrades Academy')
                    ->view('emails.student_verification');
    }
}