<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $payment;
    public $outstandingUgx;

    /**
     * @param  mixed  $student
     * @param  mixed  $payment
     * @param  float  $outstandingUgx
     */
    public function __construct($student, $payment, float $outstandingUgx)
    {
        $this->student = $student;
        $this->payment = $payment;
        $this->outstandingUgx = $outstandingUgx;
    }
public function build()
{
    // embed() returns a CID string like "cid:xxxxx"
    $logoCid = null;
    $logoPath = public_path('images/logo.png');

    if (file_exists($logoPath)) {
        try {
            $logoCid = $this->embed($logoPath);
        } catch (\Throwable $e) {
            // fallback: leave $logoCid null and log the error
            \Illuminate\Support\Facades\Log::warning('Failed to embed logo for payment receipt', [
                'path' => $logoPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    return $this->subject('Payment receipt — SuperTrades Academy')
                ->view('emails.payment_receipt')
                ->with([
                    'student' => $this->student,
                    'payment' => $this->payment,
                    'outstandingUgx' => $this->outstandingUgx,
                    'logoCid' => $logoCid,
                    'resetUrl' => $this->resetUrl ?? null,
                ]);
}
}