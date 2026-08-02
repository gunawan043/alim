<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StockOpnameSession extends Model
{
    use HasFactory;

    protected $table = 'stock_opname_sessions';

    protected $fillable = [
        'work_unit_id',
        'school_id',
        'title',
        'description',
        'scheduled_date',
        'started_date',
        'closed_date',
        'status',
        'created_by',
        'closed_by',
        'summary_notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_date' => 'date',
        'closed_date' => 'date',
    ];

    public const STATUS_PLANNED = 'planned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const OBSERVATION_FOUND = 'found';

    public const OBSERVATION_MISSING = 'missing';

    public const OBSERVATION_DAMAGED = 'damaged';

    public const OBSERVATION_MOVED = 'moved';

    public const TRANSITIONS = [
        'planned' => ['in_progress', 'cancelled'],
        'in_progress' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->session_code)) {
                $model->session_code = self::generateSessionCode();
            }
        });
    }

    public static function generateSessionCode(): string
    {
        return sprintf('SO-%s-%04d', now()->format('Ymd'), random_int(1000, 9999));
    }

    public function officers()
    {
        return $this->hasMany(StockOpnameOfficer::class, 'session_id');
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class, 'session_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function report(): array
    {
        $items = $this->items()->get();
        $found = $items->where('observed_status', self::OBSERVATION_FOUND)->count();
        $missing = $items->where('observed_status', self::OBSERVATION_MISSING)->count();
        $damaged = $items->where('observed_status', self::OBSERVATION_DAMAGED)->count();
        $moved = $items->where('observed_status', self::OBSERVATION_MOVED)->count();

        return [
            'session_code' => $this->session_code,
            'status' => $this->status,
            'total_scanned' => $items->count(),
            'found' => $found,
            'missing' => $missing,
            'damaged' => $damaged,
            'moved' => $moved,
            'items' => $items,
        ];
    }
}
