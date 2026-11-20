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
        'table_line_height',
        'table_row_padding',
        'table_border_width',
        'bold_table_header',
        'bold_subject',
        'title_text',
        'title_ar_text',
        'title_id_text',
        'title_gap',
        'title_line_height',
        'title_margin_top',
        'title_margin_bottom',
        'ident_line_height',
        'ident_row_padding',
        'footer_border',
        'footer_padding',
        'footer_left',
        'footer_center',
        'paper_size',
        'variant_layouts',
        'show_kop',
        'show_ttd_ksp',
        'show_ttd_wali',
        'show_stamp',
        'show_qr',
        'kop_height',
        'ttd_ksp_width',
        'ttd_ksp_height',
        'ttd_ksp_margin',
        'ttd_wali_width',
        'ttd_wali_height',
        'ttd_wali_margin',
        'stamp_width',
        'stamp_height',
        'stamp_margin',
        'updated_by',
    ];

    protected $casts = [
        'distribution_date' => 'date',
        'report_subject_ids' => 'array',
        'variant_layouts' => 'array',
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
        'table_line_height' => 'float',
        'table_row_padding' => 'float',
        'table_border_width' => 'float',
        'bold_table_header' => 'boolean',
        'bold_subject' => 'boolean',
        'title_gap' => 'float',
        'title_line_height' => 'float',
        'title_margin_top' => 'float',
        'title_margin_bottom' => 'float',
        'ident_line_height' => 'float',
        'ident_row_padding' => 'float',
        'footer_border' => 'boolean',
        'footer_padding' => 'float',
        'show_kop' => 'boolean',
        'show_ttd_ksp' => 'boolean',
        'show_ttd_wali' => 'boolean',
        'show_stamp' => 'boolean',
        'show_qr' => 'boolean',
        'kop_height' => 'float',
        'ttd_ksp_width' => 'float',
        'ttd_ksp_height' => 'float',
        'ttd_ksp_margin' => 'float',
        'ttd_wali_width' => 'float',
        'ttd_wali_height' => 'float',
        'ttd_wali_margin' => 'float',
        'stamp_width' => 'float',
        'stamp_height' => 'float',
        'stamp_margin' => 'float',
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
            'table_line_height' => 1.05,
            'table_row_padding' => 0.5,
            'table_border_width' => 0.3,
            'bold_table_header' => true,
            'bold_subject' => true,
            'title_text' => null,
            'title_ar_text' => null,
            'title_id_text' => null,
            'title_gap' => 0.5,
            'title_line_height' => 1.2,
            'title_margin_top' => 2.0,
            'title_margin_bottom' => 2.5,
            'ident_line_height' => 1.05,
            'ident_row_padding' => 0.15,
            'footer_border' => false,
            'footer_padding' => 2.0,
            'footer_left' => null,
            'footer_center' => null,
            'paper_size' => 'a4',
            'show_kop' => true,
            'show_ttd_ksp' => true,
            'show_ttd_wali' => true,
            'show_stamp' => true,
            'show_qr' => false,
            'kop_height' => 25.0,
            'ttd_ksp_width' => 30.0,
            'ttd_ksp_height' => 15.0,
            'ttd_ksp_margin' => 2.0,
            'ttd_wali_width' => 30.0,
            'ttd_wali_height' => 15.0,
            'ttd_wali_margin' => 2.0,
            'stamp_width' => 30.0,
            'stamp_height' => 30.0,
            'stamp_margin' => 2.0,
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
     * Pengaturan layout khusus varian dokumen: jenis (sts/sas) + bahasa (id/ar).
     * Varian yang belum pernah disimpan memakai pengaturan global sebagai default.
     *
     * @return array<string, mixed>
     */
    public function variantLayoutSettings(?string $jenis, ?string $bahasa): array
    {
        $base = $this->layoutSettings();
        $key = self::variantKey($jenis, $bahasa);

        if ($key === null) {
            return $base;
        }

        $variants = is_array($this->variant_layouts) ? $this->variant_layouts : [];
        $variant = $variants[$key] ?? null;

        return is_array($variant) ? array_merge($base, $variant) : $base;
    }

    public static function variantKey(?string $jenis, ?string $bahasa): ?string
    {
        $jenis = strtolower((string) $jenis);
        $bahasa = strtolower((string) $bahasa);

        if (! in_array($jenis, ['sts', 'sas'], true) || ! in_array($bahasa, ['id', 'ar', 'id-ar'], true)) {
            return null;
        }

        return $jenis.':'.$bahasa;
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
            'table_line_height' => (float) ($this->table_line_height ?? $defaults['table_line_height']),
            'table_row_padding' => (float) ($this->table_row_padding ?? $defaults['table_row_padding']),
            'table_border_width' => (float) ($this->table_border_width ?? $defaults['table_border_width']),
            'bold_table_header' => (bool) ($this->bold_table_header ?? $defaults['bold_table_header']),
            'bold_subject' => (bool) ($this->bold_subject ?? $defaults['bold_subject']),
            'title_text' => $this->title_text,
            'title_ar_text' => $this->title_ar_text,
            'title_id_text' => $this->title_id_text,
            'title_gap' => (float) ($this->title_gap ?? $defaults['title_gap']),
            'title_line_height' => (float) ($this->title_line_height ?? $defaults['title_line_height']),
            'title_margin_top' => (float) ($this->title_margin_top ?? $defaults['title_margin_top']),
            'title_margin_bottom' => (float) ($this->title_margin_bottom ?? $defaults['title_margin_bottom']),
            'ident_line_height' => (float) ($this->ident_line_height ?? $defaults['ident_line_height']),
            'ident_row_padding' => (float) ($this->ident_row_padding ?? $defaults['ident_row_padding']),
            'footer_border' => (bool) ($this->footer_border ?? $defaults['footer_border']),
            'footer_padding' => (float) ($this->footer_padding ?? $defaults['footer_padding']),
            'footer_left' => $this->footer_left,
            'footer_center' => $this->footer_center,
            'paper_size' => in_array($paper, ['a4', 'folio', 'letter'], true) ? $paper : 'a4',
            'show_kop' => (bool) ($this->show_kop ?? $defaults['show_kop']),
            'show_ttd_ksp' => (bool) ($this->show_ttd_ksp ?? $defaults['show_ttd_ksp']),
            'show_ttd_wali' => (bool) ($this->show_ttd_wali ?? $defaults['show_ttd_wali']),
            'show_stamp' => (bool) ($this->show_stamp ?? $defaults['show_stamp']),
            'show_qr' => (bool) ($this->show_qr ?? $defaults['show_qr']),
            'kop_height' => (float) ($this->kop_height ?? $defaults['kop_height']),
            'ttd_ksp_width' => (float) ($this->ttd_ksp_width ?? $defaults['ttd_ksp_width']),
            'ttd_ksp_height' => (float) ($this->ttd_ksp_height ?? $defaults['ttd_ksp_height']),
            'ttd_ksp_margin' => (float) ($this->ttd_ksp_margin ?? $defaults['ttd_ksp_margin']),
            'ttd_wali_width' => (float) ($this->ttd_wali_width ?? $defaults['ttd_wali_width']),
            'ttd_wali_height' => (float) ($this->ttd_wali_height ?? $defaults['ttd_wali_height']),
            'ttd_wali_margin' => (float) ($this->ttd_wali_margin ?? $defaults['ttd_wali_margin']),
            'stamp_width' => (float) ($this->stamp_width ?? $defaults['stamp_width']),
            'stamp_height' => (float) ($this->stamp_height ?? $defaults['stamp_height']),
            'stamp_margin' => (float) ($this->stamp_margin ?? $defaults['stamp_margin']),
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
