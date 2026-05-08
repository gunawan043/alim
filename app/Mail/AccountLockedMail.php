<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountLockedMail extends Mailable
{
    use SerializesModels;

    public string $userName;
    public string $email;
    public int $attempts;

    public function __construct($userName, $email, $attempts)
    {
        $this->userName = $userName;
        $this->email = $email;
        $this->attempts = $attempts;
    }

    public function build()
    {
        return $this->subject('⚠️ SECURITY ALERT: Akun Terkunci')
            ->view('auth.account-locked');
    }
}
