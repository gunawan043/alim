<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RepairCostHistory extends Model
{
    use HasFactory;

    protected $table = 'repair_cost_histories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'asset_id',
        'work_order_id',
        'repair_request_id',
        'cost_category',
        'description',
        'amount',
        'incurred_date',
        'document_number',
        'vendor_name',
        'recorded_by',
    ];

    protected $casts = [
        'incurred_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
