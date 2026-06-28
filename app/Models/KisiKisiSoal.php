<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KisiKisiSoal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kisi_kisi_soal';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'school_id', 'bank_soal_id', 'subject_id', 'grade_level_id', 'academic_year_id',
        'semester', 'jenis_ujian', 'judul', 'deskripsi', 'tingkat_sekolah',
        'peminatan', 'total_soal_target', 'total_bobot_target',
        'distribusi_kognitif', 'distribusi_kesulitan',
        'approved_by', 'approver_note', 'approved_at', 'is_active',
        'created_by',
    ];

    protected $casts = [
        'distribusi_kognitif' => 'array',
        'distribusi_kesulitan' => 'array',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'total_soal_target' => 'integer',
        'total_bobot_target' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KisiKisiSoalItem::class, 'kisi_kisi_soal_id');
    }

    /**
     * Get all TPs mapped in this kisi-kisi
     */
    public function tps(): HasMany
    {
        return $this->hasManyThrough(TujuanPembelajaran::class, KisiKisiSoalItem::class, 'kisi_kisi_soal_id', 'id');
    }

    /**
     * Count soal that actually linked to this kisi-kisi
     */
    public function soalCount(): int
    {
        return $this->items()->sum('jumlah_soal') ?: 0;
    }
}
