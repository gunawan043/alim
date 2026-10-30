<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dokumen resmi yang sudah diterbitkan dan dapat diverifikasi publik.
 *
 * Token QR disimpan sebagai hash pada `verification_token_hash`.
 * `canonical_payload` adalah data yang ditandatangani; `file_hash` opsional
 * untuk mengikat PDF final.
 */
class DocumentSignature extends Model
{
    use HasUuids;

    protected $fillable = [
        'document_type',
        'document_number',
        'title',
        'school_id',
        'academic_year_id',
        'semester',
        'subject_type',
        'subject_id',
        'validation_mode',
        'status',
        'canonical_payload',
        'payload_hash',
        'file_hash',
        'verification_token_hash',
        'signature',
        'pades_profile',
        'issued_by',
        'issued_at',
        'revoked_at',
        'revocation_reason',
        'scan_count',
        'last_scanned_at',
        'metadata',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_scanned_at' => 'datetime',
        'scan_count' => 'integer',
        'metadata' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function signers(): HasMany
    {
        return $this->hasMany(DocumentSignatureSigner::class)->orderBy('signer_order');
    }

    public function isValid(): bool
    {
        return $this->status === 'issued';
    }
}
