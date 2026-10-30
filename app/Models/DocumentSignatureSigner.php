<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penandatangan dokumen.
 *
 * `signature_status`:
 * - visual : TTD scan/stempel saja (mode QR)
 * - signed : signature PAdES berhasil
 * - failed : upaya PAdES gagal
 * - revoked: sertifikat/signature dicabut
 */
class DocumentSignatureSigner extends Model
{
    use HasUuids;

    protected $fillable = [
        'document_signature_id',
        'user_id',
        'name',
        'role',
        'signer_order',
        'certificate_subject',
        'certificate_serial',
        'certificate_expires_at',
        'signature_status',
        'pades_profile',
        'signed_at',
        'metadata',
    ];

    protected $casts = [
        'signer_order' => 'integer',
        'certificate_expires_at' => 'datetime',
        'signed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentSignature::class, 'document_signature_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
