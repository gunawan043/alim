<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzTasmianScore extends Model
{
    protected $table = 'tahfidz_tasmian_scores';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'participant_id', 'student_id', 'tasmian_session_id', 'evaluator_id',
        'tahfizh_score_raw', 'tahfizh_nilai', 'tajwid_score_raw', 'tajwid_nilai',
        'fashohah_score_raw', 'fashohah_nilai', 'nilai_akhir', 'predikat', 'catatan_penilai',
    ];

    protected $casts = [
        'tahfizh_score_raw' => 'decimal:2',
        'tahfizh_nilai' => 'decimal:2',
        'tajwid_score_raw' => 'decimal:2',
        'tajwid_nilai' => 'decimal:2',
        'fashohah_score_raw' => 'decimal:2',
        'fashohah_nilai' => 'decimal:2',
        'nilai_akhir' => 'decimal:2',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(TahfidzTasmianParticipant::class, 'participant_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TahfidzTasmianSession::class, 'tasmian_session_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function getPredikatLabelAttribute(): string
    {
        return match ($this->predikat) {
            'mumtaz' => 'Mumtaz',
            'jayyid_jiddan' => 'Jayyid Jiddan',
            'jayyid' => 'Jayyid',
            'maqbul' => 'Maqbul',
            'rasib' => 'Rasib',
            default => '—',
        };
    }

    public function getPredikatArabicAttribute(): string
    {
        return match ($this->predikat) {
            'mumtaz' => 'ممتاز',
            'jayyid_jiddan' => 'جيد جدًا',
            'jayyid' => 'جيد',
            'maqbul' => 'مقبول',
            'rasib' => 'راسب',
            default => '—',
        };
    }
}
