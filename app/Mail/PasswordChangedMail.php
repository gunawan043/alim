<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $changedAt,
        public ?string $ipAddress = null,
    ) {}

    public function build()
    {
        return $this->subject('Password Akun ALIM Berhasil Diubah')
            ->view('auth.password-changed')
            ->with([
                'name' => $this->name,
                'changedAt' => $this->changedAt,
                'ipAddress' => $this->ipAddress,
            ]);
    }
}
