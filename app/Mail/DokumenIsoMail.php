<?php

namespace App\Mail;

use App\Models\DokumenIso;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DokumenIsoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public DokumenIso $dokumen,
        public User $recipient,
        public string $aksi,
        public ?string $oldNama = null,
    ) {}

    public function envelope(): Envelope
    {
        $aksiIcon = match ($this->aksi) {
            'dibuat' => '🆕',
            'diperbarui' => '✏️',
            'dihapus' => '🗑️',
            default => '📄',
        };
        $kode = $this->dokumen->kode_dokumen ?? '—';

        return new Envelope(
            subject: "$aksiIcon [ALIM] Dokumen ISO {$this->aksi} – $kode",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dokumen-iso-notification',
            with: [
                'dokumen' => $this->dokumen,
                'recipient' => $this->recipient,
                'aksi' => $this->aksi,
                'oldNama' => $this->oldNama,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
