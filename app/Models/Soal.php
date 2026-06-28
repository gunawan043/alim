<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Soal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'soal';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'bank_soal_id',
        'tp_id',
        'tipe_soal',
        'pertanyaan',
        'gambar_path',
        'audio_path',
        'bobot_default',
        'tingkat_kesulitan_estimasi',
        'waktu_estimasi_menit',
        'status',
        'dibuat_oleh',
        'direview_oleh',
        'approved_by',
        'approved_at',
        'tags',
        'content_hash',
        'shingles_hash',
        'times_used',
    ];

    protected $casts = [
        'tags' => 'array',
        'shingles_hash' => 'array',
        'bobot_default' => 'decimal:2',
        'waktu_estimasi_menit' => 'integer',
        'times_used' => 'integer',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class);
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tp_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direview_oleh');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(SoalOption::class)->orderBy('urutan');
    }

    public function correctOptions(): HasMany
    {
        return $this->hasMany(SoalOption::class)->where('is_correct', true);
    }

    public function paketSoalItems(): HasMany
    {
        return $this->hasMany(PaketSoalItem::class);
    }

    public function analysis(): HasMany
    {
        return $this->hasMany(ItemAnalysis::class);
    }

    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('tipe_soal', $type);
    }

    public function getIsObjectivelyGradableAttribute(): bool
    {
        return in_array($this->tipe_soal, ['pg', 'bs', 'jodoh']);
    }
}
