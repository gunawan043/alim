<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IntegrationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $eventName,
        public readonly array $payload,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $studentName = $this->payload['student_name'] ?? 'Santri';
        $humanLabel = $this->humanEventLabel($this->eventName);

        $mail = (new MailMessage)
            ->subject("[Ponpes] {$humanLabel} — {$studentName}")
            ->greeting("Assalamu'alaikum Wr. Wb.")
            ->line("**{$humanLabel}** untuk ananda **{$studentName}**.");

        if ($reason = $this->payload['note'] ?? null) {
            $mail->line('Catatan: '.$reason);
        }

        $mail->line('Silakan buka Portal Orang Tua untuk detail lengkap.')
            ->salutation('Wassalam, Panitia Pondok');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->eventName,
            'payload' => $this->payload,
        ];
    }

    private function humanEventLabel(string $event): string
    {
        return match ($event) {
            'boarding.leave_approved' => 'Perizinan Diterima',
            'boarding.leave_returned' => 'Santri Kembali ke Asrama',
            'boarding.health_approved' => 'Santri Dirawat',
            'boarding.health_discharged' => 'Santri Sembuh',
            'boarding.room_damage_reported' => 'Kerusakan Kamar Dilaporkan',
            default => ucwords(str_replace(['.', '_'], [' ', ' '], $event)),
        };
    }
}
