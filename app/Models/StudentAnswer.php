<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentAnswer extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $table = 'student_answers';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'exam_attempt_id',
        'soal_id',
        'selected_option_id',
        'jawaban_text',
        'jawaban_json',
        'is_correct',
        'skor_per_soal',
        'waktu_dijawab_detik',
        'is_flagged',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'skor_per_soal' => 'decimal:2',
        'waktu_dijawab_detik' => 'integer',
        'is_flagged' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function examAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(SoalOption::class, 'selected_option_id');
    }
}
