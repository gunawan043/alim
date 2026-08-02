<?php

namespace App\Mail\Mobile;

use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentLinkedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $wali,
        public Student $student,
        public string $roleLabel
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Berhasil Terhubung dengan {$this->student->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mobile.student-linked',
            with: [
                'waliName' => $this->wali->name,
                'studentName' => $this->student->name,
                'roleLabel' => $this->roleLabel,
                'dashboardUrl' => config('app.url').'/mobile/dashboard',
            ],
        );
    }
}
