<?php

namespace App\Services\Documents;

use App\Models\DocumentSignature;
use RuntimeException;

/**
 * Adaptor PAdES.
 *
 * Private key TIDAK disimpan di aplikasi. Implementasi produksi harus
 * memanggil remote signing API/HSM milik PSrE. Selama kredensial belum
 * tersedia, `available()` bernilai false dan penerbitan mode PAdES ditolak.
 *
 * Kontrak implementasi remote:
 * - kirim PDF + metadata dokumen ke endpoint PSrE,
 * - terima PDF bertanda tangan (PAdES-B-T / B-LT),
 * - kembalikan bytes PDF tanpa mengubah isi visual.
 */
class PadesSigningService
{
    public function available(): bool
    {
        return (bool) config('documents.pades.enabled')
            && filled(config('documents.pades.endpoint'))
            && filled(config('documents.pades.client_id'));
    }

    public function profile(): string
    {
        return (string) config('documents.pades.profile', 'PAdES-B-T');
    }

    public function sign(string $pdfBytes, DocumentSignature $document): string
    {
        if (! $this->available()) {
            throw new RuntimeException(
                'PAdES belum dikonfigurasi. Isi DOCUMENTS_PADES_ENDPOINT, DOCUMENTS_PADES_CLIENT_ID, dan kredensial PSrE.'
            );
        }

        // Implementasi provider dipasang di sini setelah PSrE dipilih.
        // Jangan memakai private key lokal sebagai fallback produksi.
        throw new RuntimeException(
            'Adaptor PAdES belum diimplementasikan untuk provider PSrE yang dipilih.'
        );
    }
}
