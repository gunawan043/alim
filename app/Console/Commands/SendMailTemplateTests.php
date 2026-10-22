<?php

namespace App\Console\Commands;

use App\Mail\AccountCompromisedMail;
use App\Mail\AccountLockedMail;
use App\Mail\AccountUnlockedMail;
use App\Mail\DokumenIsoMail;
use App\Mail\IpBlockedMail;
use App\Mail\LoginOtpMail;
use App\Mail\Mobile\StudentLinkedMail;
use App\Mail\Mobile\WaliVerificationMail;
use App\Mail\PasswordChangedMail;
use App\Mail\ResetPasswordOtpMail;
use App\Mail\WaliAccessRequestMail;
use App\Mail\WaliRequestApprovedMail;
use App\Mail\WaliRequestRejectedMail;
use App\Models\DokumenIso;
use App\Models\Student;
use App\Models\Todo;
use App\Models\User;
use App\Services\SystemMailSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMailTemplateTests extends Command
{
    protected $signature = 'mail:test-templates {email : Alamat tujuan uji coba} {--only= : Hanya kirim template yang namanya mengandung teks ini}';

    protected $description = 'Kirim contoh setiap template email ke satu alamat untuk pemeriksaan visual.';

    public function handle(): int
    {
        $target = trim((string) $this->argument('email'));

        if (! filter_var($target, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tujuan tidak valid.');

            return self::FAILURE;
        }

        app(SystemMailSettingsService::class)->apply();

        $sampleUser = User::query()->first();
        $sampleStudent = Student::query()->first();
        $sampleDokumen = DokumenIso::with('divisi')->first();

        if (! $sampleUser) {
            $this->error('Tidak ada user untuk data contoh.');

            return self::FAILURE;
        }

        $messages = [
            'Account Compromised' => new AccountCompromisedMail('Admin Uji Coba', $target, 6, '127.0.0.1'),
            'Account Locked' => new AccountLockedMail('Admin Uji Coba', $target, 9, '127.0.0.1'),
            'Account Unlocked' => new AccountUnlockedMail('Admin Uji Coba', $target),
            'IP Blocked' => new IpBlockedMail('127.0.0.1', now()->addHours(24)->format('d/m/Y H:i'), 9),
            'Login OTP' => new LoginOtpMail('123456', 'Admin Uji Coba', 10),
            'Password Changed' => new PasswordChangedMail('Admin Uji Coba', now()->format('d/m/Y H:i'), '127.0.0.1'),
            'Reset Password OTP' => new ResetPasswordOtpMail('654321', 'Admin Uji Coba', 10),
        ];

        if ($sampleStudent && $sampleUser) {
            $messages['Wali Access Request'] = new WaliAccessRequestMail($sampleStudent, $sampleUser, 'SAMPLE-TOKEN');
            $messages['Wali Request Approved'] = new WaliRequestApprovedMail($sampleStudent, $sampleUser);
            $messages['Wali Request Rejected'] = new WaliRequestRejectedMail($sampleStudent, $sampleUser, 'Data belum sesuai.');
        }

        if ($sampleDokumen) {
            $messages['Dokumen ISO'] = new DokumenIsoMail($sampleDokumen, $sampleUser, 'diperbarui', 'Nama Dokumen Lama');
        }

        if ($sampleStudent) {
            $messages['Mobile: Student Linked'] = new StudentLinkedMail($sampleUser, $sampleStudent, 'Ayah');
            $messages['Mobile: Wali Verification'] = new WaliVerificationMail(
                $sampleUser,
                $sampleStudent,
                'sample-token',
                url('/sample-approve'),
                url('/sample-reject'),
                'Ayah',
            );
        }

        $sent = 0;
        $failed = 0;
        $only = strtolower(trim((string) $this->option('only')));

        foreach ($messages as $name => $mailable) {
            if ($only !== '' && ! str_contains(strtolower($name), $only)) {
                continue;
            }

            try {
                Mail::to($target)->send($mailable);
                $this->line("OK   {$name}");
                $sent++;
            } catch (\Throwable $e) {
                $this->error("FAIL {$name}: {$e->getMessage()}");
                $failed++;
            }
        }

        $todo = new Todo([
            'title' => 'Contoh Tugas untuk Pemeriksaan Template',
            'description' => 'Ini adalah contoh deskripsi tugas untuk memeriksa tampilan email reminder.',
            'priority' => 'tinggi',
            'status' => 'sedang_berjalan',
            'due_date' => now()->addDay(),
            'progress_percent' => 50,
        ]);
        $todo->setRelation('owner', $sampleUser);
        $todo->setRelation('delegatedByUser', null);
        $todo->setRelation('subtasks', collect());

        $rawMessages = [
            'Todo Reminder' => [
                'view' => 'emails.todo-reminder',
                'data' => [
                    'todo' => $todo,
                    'owner' => $sampleUser,
                    'dueText' => now()->addDay()->format('d/m/Y'),
                    'isOverdue' => false,
                    'priority' => $todo->priorityLabel,
                    'url' => url('/sample-todo'),
                ],
                'subject' => '[TEST] Todo Reminder',
            ],
            'Universal Notification' => [
                'view' => 'emails.universal-notification',
                'data' => [
                    'user' => $sampleUser,
                    'notification' => (object) [
                        'title' => 'Contoh Notifikasi Universal',
                        'priority' => 'high',
                        'module' => 'system',
                        'message' => 'Ini adalah isi contoh notifikasi universal untuk pemeriksaan template.',
                        'reference_code' => 'ALIM-TEST-001',
                        'action_url' => url('/'),
                        'action_text' => 'Buka ALIM',
                        'created_at' => now(),
                    ],
                ],
                'subject' => '[TEST] Universal Notification',
            ],
        ];

        foreach ($rawMessages as $name => $message) {
            if ($only !== '' && ! str_contains(strtolower($name), $only)) {
                continue;
            }

            try {
                Mail::send($message['view'], $message['data'], function ($mail) use ($target, $message) {
                    $mail->to($target)->subject($message['subject']);
                });
                $this->line("OK   {$name}");
                $sent++;
            } catch (\Throwable $e) {
                $this->error("FAIL {$name}: {$e->getMessage()}");
                $failed++;
            }
        }

        if (view()->exists('emails.scheduled-report')) {
            try {
                Mail::send('emails.scheduled-report', [
                    'schedule' => (object) ['name' => 'Contoh Laporan Terjadwal'],
                    'data' => [
                        'overview' => [
                            'total_jobs' => 12,
                            'total_applications' => 240,
                            'hired_count' => 18,
                        ],
                    ],
                ], function ($mail) use ($target) {
                    $mail->to($target)->subject('[TEST] Scheduled Report');
                });
                $this->line('OK   Scheduled Report');
                $sent++;
            } catch (\Throwable $e) {
                $this->error('FAIL Scheduled Report: '.$e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Selesai: {$sent} terkirim, {$failed} gagal, tujuan {$target}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
