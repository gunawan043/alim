<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Registry jenis izin (Permit Type).
 *
 * Tabel ini menyimpan master data jenis izin yang bisa ditambah, diubah,
 * diaktifkan, dan dinonaktifkan oleh admin pondok / kepala asrama.
 *
 * DormitoryLeavePolicy.permit_type masih menggunakan string code,
 * BUKAN foreign key constraint, agar:
 *   - data histori perizinan lama tetap aman walau jenis izin dihapus
 *   - pengajuan izin lama tetap bisa terbaca dengan label fallback
 */
class PermitType extends Model
{
    protected $table = 'permit_types';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'label',
        'description',
        'category',
        'icon',
        'color',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (! $model->id) {
                $model->id = (string) Str::uuid();
            }
            // Normalisasi code ke slug snake_case
            $model->code = self::normalizeCode((string) $model->code);
        });

        static::updating(function (self $model): void {
            if ($model->isDirty('code')) {
                $model->code = self::normalizeCode((string) $model->code);
            }
        });
    }

    /**
     * Normalisasi code menjadi snake_case, max 50 char, lowercase.
     * Contoh: "Izin Pulang" / "Izin Pulang" -> "izin_pulang".
     */
    public static function normalizeCode(string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            return '';
        }

        $code = Str::slug($code, '_');
        $code = strtolower($code);

        return substr($code, 0, 50);
    }

    /**
     * Daftar kategori yang dikenali.
     */
    public const CATEGORIES = ['default', 'special', 'emergency', 'custom'];

    /**
     * Scope: hanya ambil yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: urutkan untuk tampilan dropdown / list.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    /**
     * Apakah jenis izin ini termasuk kategori yang boleh muncul
     * di pilihan default sistem (pulang/sakit/darurat).
     */
    public function isDefaultCategory(): bool
    {
        return $this->category === 'default';
    }

    /**
     * Apakah jenis izin ini "khusus" (share/own quota logic berlaku).
     */
    public function isSpecialCategory(): bool
    {
        return in_array($this->category, ['special', 'emergency'], true);
    }
}
