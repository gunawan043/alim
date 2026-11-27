<?php

namespace App\Models;

use App\Services\TahfidzSubjectResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Subject extends Model
{
    protected $table = 'subjects';

    public const CATEGORY_DINAS = 'dinas';

    public const CATEGORY_PONDOK = 'pondok';

    public const CATEGORY_DINAS_PONDOK = 'dinas_pondok';

    /**
     * Kategori mapel: asal kurikulumnya (Dinas, Pondok, atau keduanya).
     *
     * @var array<string, string>
     */
    public const CATEGORY_OPTIONS = [
        self::CATEGORY_DINAS => 'Dinas',
        self::CATEGORY_PONDOK => 'Pondok',
        self::CATEGORY_DINAS_PONDOK => 'Dinas + Pondok',
    ];

    public function categoryLabel(): string
    {
        return self::CATEGORY_OPTIONS[$this->category ?? ''] ?? (string) ($this->category ?? '—');
    }

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();
        });
    }

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'name_ar',
        'subject_group_id',
        'category',
        'kelompok',
        'penilaian_format',
        'source',
        'credit_hours',
        'description',
        'is_active',
    ];

    /**
     * Boleh diajar lebih dari satu guru pada rombel yang sama?
     * Kebijakan: hanya Tahfidz (hafalan) & Hadits — karena berkaitan dengan hafalan.
     */
    public function allowsMultipleTeachers(): bool
    {
        // Penanda resmi dipakai lebih dulu; pencocokan nama hanya cadangan untuk
        // mapel yang belum ditandai admin.
        if (($this->penilaian_format ?? null) === TahfidzSubjectResolver::FORMAT_TAHFIDZ) {
            return true;
        }

        $haystack = mb_strtolower(($this->name ?? '').' '.($this->code ?? ''));

        return (bool) preg_match('/(tahfidz|tahfiz|hafalan|hadits|hadist)/', $haystack);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Rumpun mata pelajaran (entitas resmi lembaga).
     */
    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id');
    }

    public function adminBooks(): HasMany
    {
        return $this->hasMany(TeacherAdminBook::class, 'subject_id');
    }

    public function gradeLevelSubjects(): HasMany
    {
        return $this->hasMany(GradeLevelSubject::class);
    }
}
