<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Format dokumen Surat Keterangan Lulus (SKL) per sekolah.
 */
class GraduationCertificateSetting extends Model
{
    protected $table = 'graduation_certificate_settings';

    protected $keyType = 'string';

    public $incrementing = false;

    /** Kelompok mata pelajaran yang tersedia pada SKL. */
    public const GROUPS = [
        'umum' => 'Mata Pelajaran Umum',
        'kejuruan' => 'Mata Pelajaran Kejuruan',
        'pilihan' => 'Mata Pelajaran Pilihan',
        'muatan_lokal' => 'Muatan Lokal',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'paper_size', 'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
        'font_family', 'font_size', 'line_height',
        'show_kop', 'title', 'number_prefix', 'curriculum', 'criteria_text',
        'show_npsn', 'show_ijazah', 'show_photo', 'show_nilai', 'show_rata_rata',
        'show_signature', 'city', 'signer_jabatan', 'signer_name', 'signer_nip',
        'group_labels', 'updated_by',
    ];

    protected $casts = [
        'margin_top' => 'float',
        'margin_right' => 'float',
        'margin_bottom' => 'float',
        'margin_left' => 'float',
        'font_size' => 'float',
        'line_height' => 'float',
        'show_kop' => 'boolean',
        'show_npsn' => 'boolean',
        'show_ijazah' => 'boolean',
        'show_photo' => 'boolean',
        'show_nilai' => 'boolean',
        'show_rata_rata' => 'boolean',
        'show_signature' => 'boolean',
        'group_labels' => 'array',
    ];

    public static function defaultLayout(): array
    {
        return [
            'paper_size' => 'a4',
            'margin_top' => 15.0,
            'margin_right' => 15.0,
            'margin_bottom' => 15.0,
            'margin_left' => 20.0,
            'font_family' => 'Times New Roman',
            'font_size' => 11.0,
            'line_height' => 1.30,
            'show_kop' => true,
            'title' => 'SURAT KETERANGAN LULUS',
            'number_prefix' => null,
            'curriculum' => 'Kurikulum Merdeka',
            'criteria_text' => null,
            'show_npsn' => true,
            'show_ijazah' => true,
            'show_photo' => true,
            'show_nilai' => true,
            'show_rata_rata' => true,
            'show_signature' => true,
            'city' => null,
            'signer_jabatan' => 'Kepala Sekolah',
            'signer_name' => null,
            'signer_nip' => null,
            'group_labels' => self::GROUPS,
        ];
    }

    /**
     * Label kelompok yang dipakai saat cetak (default mengikuti GROUPS).
     */
    public function groupLabel(string $key): string
    {
        $labels = $this->group_labels ?: [];

        return $labels[$key] ?? self::GROUPS[$key] ?? $key;
    }

    public static function forSchool(?string $schoolId): self
    {
        if (! $schoolId) {
            return new self(self::defaultLayout());
        }

        return self::firstOrCreate(['school_id' => $schoolId], self::defaultLayout());
    }
}
