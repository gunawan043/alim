<?php

namespace App\Mail;

use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaliAccessRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public User $requester,
        public string $token,
    ) {}

    public function build()
    {
        return $this->subject('Permintaan Wali Baru untuk '.$this->student->name)
            ->view('emails.mobile.wali-access-request')
            ->with([
                'studentName' => $this->student->name,
                'requesterName' => $this->requester->name,
                'requesterEmail' => $this->requester->email,
                'token' => $this->token,
                'expiresAt' => now()->addHours(24)->format('d/m/Y H:i'),
            ]);
    }
}
