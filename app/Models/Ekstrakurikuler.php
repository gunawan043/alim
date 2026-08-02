<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ekstrakurikuler extends Model
{
    use LogsDeletion;
    use SoftDeletes;

    protected $table = 'ekstrakurikuler';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'gtk_id',
        'nama',
        'pembimbing',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'lokasi',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'kuota',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'jam_mulai' => 'string',
        'jam_selesai' => 'string',
        'kuota' => 'integer',
    ];

    const STATUS_AKTIF = 'aktif';

    const STATUS_BERHENTI = 'berhenti';

    const STATUS_OPTIONS = [
        self::STATUS_AKTIF => 'Aktif',
        self::STATUS_BERHENTI => 'Berhenti',
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

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function gtk()
    {
        return $this->belongsTo(GtkProfile::class, 'gtk_id');
    }

    public function anggota()
    {
        return $this->hasMany(EkstrakurikulerAnggota::class, 'ekstrakurikuler_id');
    }

    public function anggotaAktif()
    {
        return $this->hasMany(EkstrakurikulerAnggota::class, 'ekstrakurikuler_id')
            ->where('status', EkstrakurikulerAnggota::STATUS_AKTIF);
    }

    public function scopeBySchool($query, ?string $schoolId)
    {
        if ($schoolId) {
            return $query->where('school_id', $schoolId);
        }

        return $query;
    }

    public function scopeAktif($query)
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    public function scopeByStatus($query, ?string $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }

        return $query;
    }
}
