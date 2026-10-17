<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountUnlockedMail extends Mailable
{
    use SerializesModels;

    public string $userName;

    public string $email;

    public function __construct(string $userName, string $email)
    {
        $this->userName = $userName;
        $this->email = $email;
    }

    public function build()
    {
        return $this->subject('✅ [ALIM] Akun Berhasil Dibuka Kembali')
            ->view('emails.account-unlocked');
    }
}
