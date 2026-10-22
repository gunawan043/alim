<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;

    public string $name;

    public int $expiryMinutes;

    public function __construct(string $otp, string $name, int $expiryMinutes = 10)
    {
        $this->otp = $otp;
        $this->name = $name;
        $this->expiryMinutes = $expiryMinutes;
    }

    public function build()
    {
        return $this->subject('Kode OTP Reset Password Akun')
            ->view('auth.reset-password-otp')
            ->with([
                'otp' => $this->otp,
                'name' => $this->name,
                'expiryMinutes' => $this->expiryMinutes,
            ]);
    }
}
