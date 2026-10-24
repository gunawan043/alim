<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Pengaturan tanggal pembagian dan tata letak PDF rapor per satuan
 * pendidikan + TA + semester. Dipakai dokumen rapor (preview, cetak, arsip).
 */
class RaporDistributionSetting extends Model
{
    protected $table = 'rapor_distribution_settings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'semester',
        'distribution_date',
        'city',
        'notes',
        'report_subject_ids',
        'margin_top',
        'margin_right',
        'margin_bottom',
        'margin_left',
        'font_size',
        'title_font_size',
        'table_font_size',
        'info_font_size',
        'footer_font_size',
        'line_height',
        'paper_size',
        'show_kop',
        'show_ttd_ksp',
        'show_ttd_wali',
        'show_stamp',
        'kop_height',
        'ttd_ksp_width',
        'ttd_ksp_height',
        'ttd_wali_width',
        'ttd_wali_height',
        'stamp_width',
        'stamp_height',
        'updated_by',
    ];

    protected $casts = [
        'distribution_date' => 'date',
        'report_subject_ids' => 'array',
        'margin_top' => 'float',
        'margin_right' => 'float',
        'margin_bottom' => 'float',
        'margin_left' => 'float',
        'font_size' => 'float',
        'title_font_size' => 'float',
        'table_font_size' => 'float',
        'info_font_size' => 'float',
        'footer_font_size' => 'float',
        'line_height' => 'float',
        'show_kop' => 'boolean',
        'show_ttd_ksp' => 'boolean',
        'show_ttd_wali' => 'boolean',
        'show_stamp' => 'boolean',
        'kop_height' => 'float',
        'ttd_ksp_width' => 'float',
        'ttd_ksp_height' => 'float',
        'ttd_wali_width' => 'float',
        'ttd_wali_height' => 'float',
        'stamp_width' => 'float',
        'stamp_height' => 'float',
    ];

    /**
     * Nilai default tata letak PDF rapor. Nilai margin memakai satuan mm.
     *
     * @return array<string, mixed>
     */
    public static function defaultLayout(): array
    {
        return [
            'margin_top' => 10.0,
            'margin_right' => 15.0,
            'margin_bottom' => 15.0,
            'margin_left' => 15.0,
            'font_size' => 11.0,
            'title_font_size' => 14.0,
            'table_font_size' => 10.0,
            'info_font_size' => 10.0,
            'footer_font_size' => 8.0,
            'line_height' => 1.4,
            'paper_size' => 'a4',
            'show_kop' => true,
            'show_ttd_ksp' => true,
            'show_ttd_wali' => true,
            'show_stamp' => true,
            'kop_height' => 25.0,
            'ttd_ksp_width' => 30.0,
            'ttd_ksp_height' => 15.0,
            'ttd_wali_width' => 30.0,
            'ttd_wali_height' => 15.0,
            'stamp_width' => 30.0,
            'stamp_height' => 30.0,
        ];
    }

    /**
     * ID mapel yang masuk rapor, sesuai urutan tampil.
     * Null berarti semua mapel tersedia.
     *
     * @return array<int, string>|null
     */
    public function reportSubjectIds(): ?array
    {
        if (! is_array($this->report_subject_ids)) {
            return null;
        }

        $ids = array_values(array_filter($this->report_subject_ids, fn ($id) => is_string($id) && $id !== ''));

        return $ids === [] ? null : $ids;
    }

    /**
     * @return array<string, mixed>
     */
    public function layoutSettings(): array
    {
        $defaults = self::defaultLayout();
        $paper = (string) ($this->paper_size ?: $defaults['paper_size']);

        return [
            'margin_top' => (float) ($this->margin_top ?? $defaults['margin_top']),
            'margin_right' => (float) ($this->margin_right ?? $defaults['margin_right']),
            'margin_bottom' => (float) ($this->margin_bottom ?? $defaults['margin_bottom']),
            'margin_left' => (float) ($this->margin_left ?? $defaults['margin_left']),
            'font_size' => (float) ($this->font_size ?? $defaults['font_size']),
            'title_font_size' => (float) ($this->title_font_size ?? $defaults['title_font_size']),
            'table_font_size' => (float) ($this->table_font_size ?? $defaults['table_font_size']),
            'info_font_size' => (float) ($this->info_font_size ?? $defaults['info_font_size']),
            'footer_font_size' => (float) ($this->footer_font_size ?? $defaults['footer_font_size']),
            'line_height' => (float) ($this->line_height ?? $defaults['line_height']),
            'paper_size' => in_array($paper, ['a4', 'folio', 'letter'], true) ? $paper : 'a4',
            'show_kop' => (bool) ($this->show_kop ?? $defaults['show_kop']),
            'show_ttd_ksp' => (bool) ($this->show_ttd_ksp ?? $defaults['show_ttd_ksp']),
            'show_ttd_wali' => (bool) ($this->show_ttd_wali ?? $defaults['show_ttd_wali']),
            'show_stamp' => (bool) ($this->show_stamp ?? $defaults['show_stamp']),
            'kop_height' => (float) ($this->kop_height ?? $defaults['kop_height']),
            'ttd_ksp_width' => (float) ($this->ttd_ksp_width ?? $defaults['ttd_ksp_width']),
            'ttd_ksp_height' => (float) ($this->ttd_ksp_height ?? $defaults['ttd_ksp_height']),
            'ttd_wali_width' => (float) ($this->ttd_wali_width ?? $defaults['ttd_wali_width']),
            'ttd_wali_height' => (float) ($this->ttd_wali_height ?? $defaults['ttd_wali_height']),
            'stamp_width' => (float) ($this->stamp_width ?? $defaults['stamp_width']),
            'stamp_height' => (float) ($this->stamp_height ?? $defaults['stamp_height']),
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function forContext(?string $schoolId, ?string $academicYearId, ?string $semester): ?self
    {
        if (! $schoolId || ! $academicYearId || ! $semester) {
            return null;
        }

        return static::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->first();
    }
}
