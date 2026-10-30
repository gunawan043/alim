<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalTaskType extends Model
{
    use HasUuids;

    protected $table = 'additional_task_types';

    protected $fillable = [
        'jenis_gtk_id',
        'nama',
        'kode',
        'deskripsi',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function jenisGtk(): BelongsTo
    {
        return $this->belongsTo(JenisGtk::class, 'jenis_gtk_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /**
     * Cari jenis tugas dari id, kode (legacy pakai underscore), atau nama persis.
     * Dipakai agar nama tugas tidak pernah dikarang bebas dari input pengguna.
     */
    public static function resolve(?string $id, ?string $name = null, ?string $code = null): ?self
    {
        if ($id) {
            $byId = static::find($id);
            if ($byId) {
                return $byId;
            }
        }

        if ($code) {
            $normalized = str_replace('_', '-', mb_strtolower(trim($code)));
            $byCode = static::query()->whereRaw('LOWER(kode) = ?', [$normalized])->first();
            if ($byCode) {
                return $byCode;
            }
        }

        if ($name) {
            return static::query()
                ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower(trim($name))])
                ->first();
        }

        return null;
    }
}
