<?php

namespace App\Mail\Mobile;

use App\Models\User;
use App\Models\Student;
use App\Models\WaliSantri;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaliVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $wali,
        public Student $student,
        public string $approvalToken,
        public string $approveUrl,
        public string $rejectUrl,
        public string $roleLabel
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Verifikasi:Permintaan Jadi Wali dari {$this->wali->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mobile.wali-verification',
            with: [
                'waliName'  => $this->wali->name,
                'studentName' => $this->student->name,
                'roleLabel'  => $this->roleLabel,
                'approveUrl' => $this->approveUrl,
                'rejectUrl'  => $this->rejectUrl,
                'expiresAt'  => now()->addHours(48)->format('d M Y, H:i'),
            ],
        );
    }
}