<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EkstrakurikulerAnggota extends Model
{
    use LogsDeletion;
    use SoftDeletes;

    protected $table = 'ekstrakurikuler_anggota';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'ekstrakurikuler_id',
        'student_id',
        'tanggal_bergabung',
        'tanggal_keluar',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_bergabung' => 'date',
        'tanggal_keluar' => 'date',
    ];

    const STATUS_AKTIF = 'aktif';

    const STATUS_KELUAR = 'keluar';

    const STATUS_LULUS = 'lulus';

    const STATUS_OPTIONS = [
        self::STATUS_AKTIF => 'Aktif',
        self::STATUS_KELUAR => 'Keluar',
        self::STATUS_LULUS => 'Lulus',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class, 'ekstrakurikuler_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function scopeByEkstrakurikuler($query, ?string $ekstrakurikulerId)
    {
        if ($ekstrakurikulerId) {
            return $query->where('ekstrakurikuler_id', $ekstrakurikulerId);
        }

        return $query;
    }

    public function scopeAktif($query)
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    public function scopeByStudent($query, ?string $studentId)
    {
        if ($studentId) {
            return $query->where('student_id', $studentId);
        }

        return $query;
    }
}
