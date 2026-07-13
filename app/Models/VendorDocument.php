<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocument extends Model
{
    use HasFactory;

    protected $table = 'vendor_documents';

    protected $fillable = [
        'vendor_id', 'title', 'document_type', 'document_number',
        'issued_at', 'expired_at', 'file_path', 'mime_type',
        'file_size', 'uploaded_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expired_at' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->lt(now());
    }
}