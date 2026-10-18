<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class TahfidzTasmianParticipant extends Model
{
    protected $table = 'tahfidz_tasmian_participants';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'tasmian_session_id', 'student_id', 'academic_year_id', 'nomor_urut', 'juz_tasmi',
        'juz_detail', 'materi_detail', 'mustami_id', 'waktu_mulai', 'waktu_selesai', 'tempat', 'status',
    ];

    protected $casts = [
        'nomor_urut' => 'integer',
        'juz_tasmi' => 'integer',
        'juz_detail' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TahfidzTasmianSession::class, 'tasmian_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function mustami(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mustami_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function score(): HasOne
    {
        return $this->hasOne(TahfidzTasmianScore::class, 'participant_id');
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(TahfidzCertificate::class, 'tasmian_participant_id');
    }

    public function getJuzListAttribute(): array
    {
        $detail = $this->juz_detail ?: [];

        if (! empty($detail)) {
            return array_values(array_map('intval', $detail));
        }

        return $this->juz_tasmi ? [(int) $this->juz_tasmi] : [];
    }

    public function getJuzLabelAttribute(): string
    {
        $juz = $this->juz_list;

        return empty($juz) ? '—' : 'Juz '.implode(', ', $juz);
    }
}
