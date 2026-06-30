<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\LogsDeletion;
use Illuminate\Support\Str;

class BankSoal extends Model
{
    use HasFactory, SoftDeletes, LogsDeletion;

    protected $table = 'bank_soal';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'school_id',
        'subject_id',
        'fase',
        'nama',
        'deskripsi',
        'jenis_soal',
        'tingkat_kesulitan_target',
        'is_public',
        'shared_scope',
        'owner_user_id',
        'allow_cross_teacher_clone',
        'total_soal',
        'distribusi_kesulitan_aktual',
        'created_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'allow_cross_teacher_clone' => 'boolean',
        'distribusi_kesulitan_aktual' => 'array',
        'total_soal' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(\App\Models\School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    public function soalApproved(): HasMany
    {
        return $this->hasMany(Soal::class)->where('status', 'approved');
    }

    public function tujuanPembelajaran(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(TujuanPembelajaran::class, 'bank_soal_tp', 'bank_soal_id', 'tp_id')
            ->withTimestamps();
    }

    public function scopeOwnedBy($query, string $userId)
    {
        return $query->where('owner_user_id', $userId);
    }

    public function scopeAccessibleBy($query, string $userId, ?string $schoolId = null)
    {
        return $query->where(function ($q) use ($userId, $schoolId) {
            $q->where('owner_user_id', $userId)
                ->orWhere('is_public', true)
                ->where(function ($q2) use ($schoolId) {
                    if ($schoolId) {
                        $q2->where('shared_scope', 'internal_school')
                            ->where('school_id', $schoolId);
                    } else {
                        $q2->where('shared_scope', '<>', 'private');
                    }
                });
        });
    }
}
