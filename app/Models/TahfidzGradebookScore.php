<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Nilai empat kolom tahfidz untuk satu santri pada satu buku halaqah.
 */
class TahfidzGradebookScore extends Model
{
    protected $table = 'tahfidz_gradebook_scores';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'tahfidz_gradebook_id', 'student_id', 'study_group_id',
        'npt', 'ziyadah', 'rabt', 'tikrar', 'sts', 'sas', 'nr_final', 'ket',
    ];

    protected $casts = [
        'npt' => 'float',
        'ziyadah' => 'float',
        'rabt' => 'float',
        'tikrar' => 'float',
        'sts' => 'float',
        'sas' => 'float',
        'nr_final' => 'float',
    ];

    public function gradebook(): BelongsTo
    {
        return $this->belongsTo(TahfidzGradebook::class, 'tahfidz_gradebook_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }
}
