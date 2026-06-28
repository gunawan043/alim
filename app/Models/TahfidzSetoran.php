<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzSetoran extends Model
{
    protected $table = 'tahfidz_setorans';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id', 'teacher_id', 'tahfidz_group_id', 'academic_year_id',
        'setoran_date', 'week_number', 'setoran_type', 'metode_pembelajaran',
        'surah_start_id', 'ayat_start', 'surah_end_id', 'ayat_end',
        'juz', 'halaman_start', 'halaman_end', 'jumlah_halaman', 'jumlah_baris',
        'hasil_hafalan', 'khofi', 'jali', 'nilai_setoran',
        'capaian_target', 'keterangan_capaian',
        'status', 'catatan_guru',
    ];

    protected $casts = [
        'setoran_date' => 'date',
        'week_number' => 'integer',
        'ayat_start' => 'integer',
        'ayat_end' => 'integer',
        'juz' => 'integer',
        'halaman_start' => 'decimal:1',
        'halaman_end' => 'decimal:1',
        'jumlah_halaman' => 'decimal:1',
        'jumlah_baris' => 'integer',
        'hasil_hafalan' => 'integer',
        'khofi' => 'integer',
        'jali' => 'integer',
        'nilai_setoran' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getSetoranTypeLabelAttribute(): string
    {
        return match ($this->setoran_type) {
            'ziyadah' => 'Ziyadah (Hafalan Baru)',
            'murajaah' => 'Murajaah (Pengulangan)',
            'tikror' => 'Tikror (Penugasan Ulang)',
            default => ucfirst($this->setoran_type ?? ''),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'lulus' => 'Lulus',
            'ulang' => 'Ulang',
            'ditunda' => 'Ditunda',
            default => ucfirst($this->status ?? ''),
        };
    }
}
