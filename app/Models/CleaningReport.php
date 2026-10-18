<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CleaningReport extends Model
{
    protected $table = 'cleaning_reports';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'area_id', 'report_date', 'officer_id', 'substitute_for_user_id',
        'absence_reason', 'overall_condition', 'score', 'notes', 'status',
        'verified_by', 'verified_at', 'coordinator_note', 'created_by',
    ];

    protected $casts = [
        'report_date' => 'date', 'verified_at' => 'datetime', 'score' => 'integer',
    ];

    public const CONDITION_OPTIONS = ['sangat_bersih', 'bersih', 'tidak_bersih'];
    public const STATUS_OPTIONS = ['submitted', 'verified', 'rejected'];

    public function area(): BelongsTo { return $this->belongsTo(CleaningArea::class, 'area_id'); }
    public function officer(): BelongsTo { return $this->belongsTo(User::class, 'officer_id'); }
    public function substituteFor(): BelongsTo { return $this->belongsTo(User::class, 'substitute_for_user_id'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(CleaningReportItem::class, 'report_id'); }
}
