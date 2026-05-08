<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class WorkUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'code',
        'type',
        'parent_id',
        'induk',
        'divisi_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const TYPE_OPTIONS = [
        'Unsur Pimpinan',
        'Unit Akademik',
        'Unit Penunjang Akademik',
        'Unit Administrasi',
        'Unit Pelayanan',
        'Unit Homem Publikasi'
    ];

    const PARENT_OPTIONS = [
        'MUDIR' => 'MUDIR',
        'WADIR 1' => 'WADIR 1',
        'WADIR 2' => 'WADIR 2',
    ];

    public static function generateUniqueCode($divisiId = null, $parentId = null, $type = null)
    {
        if (!$divisiId) {
            return null;
        }

        $divisi = Divisi::find($divisiId);
        if (!$divisi || !$divisi->kode) {
            return null;
        }

        $prefix = strtoupper(trim($divisi->kode));

        $lastUnit = self::withTrashed()->where('code', 'LIKE', "{$prefix}-%")
            ->orderBy('code', 'desc')
            ->first();

        $number = 1;
        if ($lastUnit && preg_match('/-(\d+)$/', $lastUnit->code, $match)) {
            $number = intval($match[1]) + 1;
        }

        return $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    // RELATIONSHIPS
    public function parent()
    {
        return $this->belongsTo(WorkUnit::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(WorkUnit::class, 'parent_id');
    }

    public function gtkProfiles()
    {
        return $this->hasMany(GtkProfile::class);
    }

    public function gtkWorkUnits()
    {
        return $this->hasMany(GtkWorkUnit::class);
    }

    public function transferRequestsFrom()
    {
        return $this->hasMany(GtkTransferRequest::class, 'from_work_unit_id');
    }

    public function transferRequestsTo()
    {
        return $this->hasMany(GtkTransferRequest::class, 'to_work_unit_id');
    }

    public function workUnitHistoriesFrom()
    {
        return $this->hasMany(GtkWorkUnitHistory::class, 'from_work_unit_id');
    }

    public function workUnitHistoriesTo()
    {
        return $this->hasMany(GtkWorkUnitHistory::class, 'to_work_unit_id');
    }

    public function gtkRequests()
    {
        return $this->hasMany(GtkRequest::class);
    }

    public function recruitments()
    {
        return $this->hasMany(GtkRecruitment::class);
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // ACCESSORS
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => ucwords(strtolower($value)),
        );
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => strtoupper($value),
        );
    }

    // AUDIT LOGGING
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
        static::created(function ($workUnit) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'WORK_UNIT_CREATED',
                'table_name' => 'work_units',
                'record_id' => $workUnit->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::updated(function ($workUnit) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'WORK_UNIT_UPDATED',
                'table_name' => 'work_units',
                'record_id' => $workUnit->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::deleted(function ($workUnit) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'WORK_UNIT_DELETED',
                'table_name' => 'work_units',
                'record_id' => $workUnit->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    public static function getParentOptions()
    {
        return array_merge(['' => 'Tidak Ada Induk'], self::PARENT_OPTIONS);
    }

    public static function getJenisOptions()
    {
        return array_combine(self::TYPE_OPTIONS, self::TYPE_OPTIONS);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function school()
    {
        return $this->hasOne(School::class);
    }

    public function dormitories()
    {
        return $this->hasMany(Dormitory::class);
    }

    public function recruitmentJobs()
    {
        return $this->hasMany(RecruitmentJob::class, 'work_unit_id_uuid', 'uuid');
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    public static function getDivisiOptions()
    {
        return Divisi::active()->orderBy('nama')->pluck('nama', 'id')->toArray();
    }
}