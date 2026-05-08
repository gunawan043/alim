<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AdminPresensiMapel extends Model
{
    protected $table = 'admin_presensi_mapel';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'admin_book_id', 'academic_year_id', 'semester',
        'attendance_date', 'status', 'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function adminBook(): BelongsTo
    {
        return $this->belongsTo(TeacherAdminBook::class, 'admin_book_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function presensiSiswa(): HasMany
    {
        return $this->hasMany(AdminPresensiSiswa::class, 'presensi_mapel_id');
    }
}