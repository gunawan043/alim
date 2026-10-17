<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class IpBlockedMail extends Mailable
{
    use SerializesModels;

    public string $ipAddress;

    public string $blockedUntil;

    public int $attempts;

    public function __construct(string $ipAddress, string $blockedUntil, int $attempts)
    {
        $this->ipAddress = $ipAddress;
        $this->blockedUntil = $blockedUntil;
        $this->attempts = $attempts;
    }

    public function build(): self
    {
        return $this->subject('🔒 [ALIM] Alamat IP Diblokir Sementara')
            ->view('emails.ip-blocked');
    }
}
