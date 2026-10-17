<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Regulation extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi massal.
     */
    protected $fillable = [
        'bab', 'pasal', 'title', 'content', 'order',
    ];

    /**
     * Attributes yang harus diarray-kan (untuk UUID jika perlu).
     */
    protected $guarded = ['id'];

    /**
     * Tipe atribut untuk casting.
     */
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Relasi ke createdBy (opsional, bila ada field created_by)
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke updatedBy (opsional, bila ada field updated_by)
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
