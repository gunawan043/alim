<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminPresensiSiswa extends Model
{
    protected $table = 'admin_presensi_siswa';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'presensi_mapel_id', 'student_id', 'status', 'notes',
    ];

    public function presensiMapel(): BelongsTo
    {
        return $this->belongsTo(AdminPresensiMapel::class, 'presensi_mapel_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
