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

    public string $ipAddress;

    public string $loginUrl;

    public function __construct(string $userName, string $email, int $attempts, string $ipAddress = 'Unknown')
    {
        $this->userName = $userName;
        $this->email = $email;
        $this->attempts = $attempts;
        $this->ipAddress = $ipAddress;
        $this->loginUrl = route('login');
    }

    public function build()
    {
        return $this->subject('⚠️ SECURITY ALERT: Akun Terkunci')
            ->view('auth.account-locked');
    }
}
