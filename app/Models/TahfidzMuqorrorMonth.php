<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzMuqorrorMonth extends Model
{
    protected $table = 'tahfidz_muqorror_months';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'plan_id', 'month', 'target_juz_start', 'target_juz_end', 'target_pages',
        'target_lines_month', 'target_lines_day', 'active_days',
        'from_surah_name', 'from_ayat', 'to_surah_name', 'to_ayat',
        'exam_type', 'target_semester', 'target_mid_semester',
        'ziyadah_meetings', 'rabth_meetings', 'meeting_no',
        'alumni_material', 'non_alumni_material', 'notes', 'detail', 'sort_order',
    ];

    protected $casts = [
        'target_pages' => 'decimal:1',
        'target_lines_day' => 'decimal:1',
        'detail' => 'array',
        'month' => 'integer',
        'target_juz_start' => 'integer',
        'target_juz_end' => 'integer',
        'target_lines_month' => 'integer',
        'active_days' => 'integer',
        'from_ayat' => 'integer',
        'to_ayat' => 'integer',
        'ziyadah_meetings' => 'integer',
        'rabth_meetings' => 'integer',
        'meeting_no' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TahfidzMuqorrorPlan::class, 'plan_id');
    }
}
