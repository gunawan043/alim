<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminJurnalPembelajaran extends Model
{
    protected $table = 'admin_jurnal_pembelajaran';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'admin_book_id', 'academic_year_id', 'semester',
        'prosem_item_id', 'perangkat_pembelajaran_id', 'tujuan_pembelajaran_id',
        'meeting_number', 'meeting_date', 'time_in', 'time_out',
        'material', 'teacher_signature', 'class_leader_signature',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'time_in' => 'datetime:H:i',
        'time_out' => 'datetime:H:i',
    ];

    public function adminBook(): BelongsTo
    {
        return $this->belongsTo(TeacherAdminBook::class, 'admin_book_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function prosemItem(): BelongsTo
    {
        return $this->belongsTo(ProsemItem::class, 'prosem_item_id');
    }

    public function perangkat(): BelongsTo
    {
        return $this->belongsTo(PerangkatPembelajaran::class, 'perangkat_pembelajaran_id');
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tujuan_pembelajaran_id');
    }
}
