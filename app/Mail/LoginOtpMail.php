<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $name,
        public int $expiryMinutes = 10,
    ) {}

    public function build()
    {
        return $this->subject('Kode Verifikasi Login ALIM')
            ->view('auth.login-otp')
            ->with([
                'otp' => $this->otp,
                'name' => $this->name,
                'expiryMinutes' => $this->expiryMinutes,
            ]);
    }
}
