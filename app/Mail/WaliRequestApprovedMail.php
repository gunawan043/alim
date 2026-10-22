<?php

namespace App\Mail;

use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaliRequestApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public User $approver,
    ) {}

    public function build()
    {
        return $this->subject('Permintaan Wali Disetujui: '.$this->student->name)
            ->view('emails.mobile.wali-request-approved')
            ->with([
                'studentName' => $this->student->name,
                'approverName' => $this->approver->name,
            ]);
    }
}
