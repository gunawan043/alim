<?php

namespace App\Services\Documents;

use App\Models\DocumentSignature;
use App\Models\DocumentSignatureSigner;
use App\Models\User;
use App\Services\Ljk\LjkQrRenderer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Penerbitan dan verifikasi dokumen resmi.
 *
 * Mode `qr`     : dokumen diverifikasi lewat registry + token QR.
 * Mode `pades`  : dokumen ditandatangani secara kriptografis (PAdES).
 *
 * Token asli hanya dikembalikan sekali saat penerbitan; database menyimpan
 * hash-nya. Private key sertifikat tidak pernah disimpan di aplikasi ini.
 */
class DocumentSigningService
{
    public function __construct(private PadesSigningService $pades) {}

    /**
     * Terbitkan registry dokumen.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $signers
     * @return array{document: DocumentSignature, token: string, url: string, qr_data_uri: string}
     */
    public function issue(array $data, array $signers = []): array
    {
        $mode = $this->resolveMode((string) ($data['document_type'] ?? 'lainnya'), $data['validation_mode'] ?? null);

        if ($mode === 'pades' && ! $this->pades->available()) {
            throw new RuntimeException(
                'Mode PAdES belum aktif. Konfigurasikan sertifikat PSrE dan PadesSigningService sebelum menerbitkan dokumen PAdES.'
            );
        }

        $token = bin2hex(random_bytes(32));
        $issuedAt = isset($data['issued_at'])
            ? Carbon::parse($data['issued_at'])
            : now();

        $signerPayload = $this->normalizeSigners($signers);
        $canonical = $this->canonicalPayload($data, $signerPayload, $issuedAt);
        $payloadHash = hash('sha256', $canonical);
        $signature = $this->signPayload($canonical);

        $document = DB::transaction(function () use ($data, $mode, $token, $issuedAt, $canonical, $payloadHash, $signature, $signerPayload) {
            $document = DocumentSignature::create([
                'document_type' => (string) $data['document_type'],
                'document_number' => $data['document_number'] ?? null,
                'title' => (string) $data['title'],
                'school_id' => $data['school_id'] ?? null,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'semester' => $data['semester'] ?? null,
                'subject_type' => $data['subject_type'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
                'validation_mode' => $mode,
                'status' => 'issued',
                'canonical_payload' => $canonical,
                'payload_hash' => $payloadHash,
                'verification_token_hash' => hash('sha256', $token),
                'signature' => $signature,
                'pades_profile' => $mode === 'pades' ? ($data['pades_profile'] ?? 'PAdES-B-T') : null,
                'issued_by' => $data['issued_by'] ?? auth()->id(),
                'issued_at' => $issuedAt,
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($signerPayload as $signer) {
                DocumentSignatureSigner::create([
                    'document_signature_id' => $document->id,
                    'user_id' => $signer['user_id'] ?? null,
                    'name' => $signer['name'],
                    'role' => $signer['role'] ?? null,
                    'signer_order' => $signer['signer_order'] ?? 1,
                    'certificate_subject' => $signer['certificate_subject'] ?? null,
                    'certificate_serial' => $signer['certificate_serial'] ?? null,
                    'certificate_expires_at' => $signer['certificate_expires_at'] ?? null,
                    'signature_status' => $mode === 'pades' ? 'pending' : 'visual',
                    'pades_profile' => $signer['pades_profile'] ?? null,
                    'signed_at' => $mode === 'pades' ? null : $issuedAt,
                    'metadata' => $signer['metadata'] ?? null,
                ]);
            }

            return $document;
        });

        $url = $this->verificationUrl($document, $token);

        return [
            'document' => $document->load('signers'),
            'token' => $token,
            'url' => $url,
            'qr_data_uri' => $this->qrDataUri($url),
        ];
    }

    /**
     * Simpan hash PDF final setelah render/signature.
     */
    public function finalize(DocumentSignature $document, string $pdfBytes): DocumentSignature
    {
        $document->update(['file_hash' => hash('sha256', $pdfBytes)]);

        return $document->refresh();
    }

    /**
     * Tandatangani PDF dengan PAdES. Mengembalikan bytes PDF bertanda tangan.
     */
    public function signPades(DocumentSignature $document, string $pdfBytes): string
    {
        if ($document->validation_mode !== 'pades') {
            return $pdfBytes;
        }

        $signed = $this->pades->sign($pdfBytes, $document);

        $this->finalize($document, $signed);
        $document->signers()->update([
            'signature_status' => 'signed',
            'signed_at' => now(),
        ]);

        return $signed;
    }

    /**
     * Verifikasi integritas payload + HMAC registry.
     */
    public function verifyIntegrity(DocumentSignature $document): bool
    {
        $expectedHash = hash('sha256', (string) $document->canonical_payload);

        if (! hash_equals($expectedHash, (string) $document->payload_hash)) {
            return false;
        }

        $expectedSignature = $this->signPayload((string) $document->canonical_payload);

        if (! hash_equals($expectedSignature, (string) $document->signature)) {
            return false;
        }

        // Mode PAdES baru sah setelah semua penandatangan memiliki signature.
        if ($document->validation_mode === 'pades') {
            $signers = $document->relationLoaded('signers')
                ? $document->signers
                : $document->signers()->get();

            return $signers->isNotEmpty()
                && $signers->every(fn ($signer) => $signer->signature_status === 'signed');
        }

        return true;
    }

    public function findByToken(string $token): ?DocumentSignature
    {
        if ($token === '' || strlen($token) < 32) {
            return null;
        }

        return DocumentSignature::query()
            ->with(['school:id,name', 'academicYear:id,name', 'signers'])
            ->where('verification_token_hash', hash('sha256', $token))
            ->first();
    }

    public function revoke(DocumentSignature $document, string $reason, ?User $user = null): DocumentSignature
    {
        $document->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);

        $document->signers()->update(['signature_status' => 'revoked']);

        return $document->refresh();
    }

    public function markScanned(DocumentSignature $document): void
    {
        $document->increment('scan_count');
        $document->forceFill(['last_scanned_at' => now()])->save();
    }

    public function verificationUrl(DocumentSignature $document, string $token): string
    {
        return route('documents.verify', ['token' => $token]);
    }

    public function qrDataUri(string $url): string
    {
        // Dompdf tidak merender SVG data-URI dengan andal; gunakan PNG via GD.
        return LjkQrRenderer::pngDataUri($url, 300, 3);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSigners(array $signers): array
    {
        return collect($signers)
            ->filter(fn ($signer) => ! empty($signer['name']))
            ->values()
            ->map(fn ($signer, $index) => [
                'user_id' => $signer['user_id'] ?? null,
                'name' => (string) $signer['name'],
                'role' => $signer['role'] ?? null,
                'signer_order' => $signer['signer_order'] ?? $index + 1,
                'certificate_subject' => $signer['certificate_subject'] ?? null,
                'certificate_serial' => $signer['certificate_serial'] ?? null,
                'certificate_expires_at' => $signer['certificate_expires_at'] ?? null,
                'pades_profile' => $signer['pades_profile'] ?? null,
                'metadata' => $signer['metadata'] ?? null,
            ])
            ->all();
    }

    /**
     * Payload kanonik: urutan kunci stabil agar hash dapat diverifikasi ulang.
     */
    private function canonicalPayload(array $data, array $signers, Carbon $issuedAt): string
    {
        $payload = [
            'schema' => 'alim.document.v1',
            'document_type' => (string) ($data['document_type'] ?? ''),
            'document_number' => (string) ($data['document_number'] ?? ''),
            'title' => (string) ($data['title'] ?? ''),
            'school_id' => (string) ($data['school_id'] ?? ''),
            'academic_year_id' => (string) ($data['academic_year_id'] ?? ''),
            'semester' => (string) ($data['semester'] ?? ''),
            'subject_type' => (string) ($data['subject_type'] ?? ''),
            'subject_id' => (string) ($data['subject_id'] ?? ''),
            'issued_at' => $issuedAt->toIso8601String(),
            'signers' => collect($signers)->map(fn ($signer) => [
                'name' => $signer['name'],
                'role' => $signer['role'] ?? '',
                'signer_order' => $signer['signer_order'],
            ])->all(),
        ];

        $this->ksortRecursive($payload);

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function signPayload(string $canonical): string
    {
        $key = (string) config('documents.signing_key');

        if ($key === '') {
            $key = (string) config('app.key');
        }

        return hash_hmac('sha256', $canonical, $key);
    }

    private function resolveMode(string $documentType, ?string $requested): string
    {
        $mode = $requested ?: config("documents.modes.{$documentType}", config('documents.default_mode', 'qr'));

        return $mode === 'pades' ? 'pades' : 'qr';
    }

    private function ksortRecursive(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }
}
