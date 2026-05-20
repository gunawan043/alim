<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetLoan extends Model
{
    use HasFactory;

    protected $table = 'asset_loans';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'asset_id',
        'work_unit_id',
        'school_id',
        'borrower_id',
        'purpose',
        'loan_date',
        'loan_time',
        'expected_return_date',
        'actual_return_date',
        'actual_return_time',
        'condition_on_loan',
        'condition_on_return',
        'status',
        'approved_by',
        'approved_at',
        'returned_to',
        'damage_notes',
        'related_agenda_id',
        'notes',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
        'approved_at' => 'datetime',
    ];

    const STATUS_OPTIONS = [
        'pending', 'approved', 'dipinjam', 'dikembalikan',
        'terlambat', 'hilang', 'dibatalkan',
    ];

    const CONDITION_OPTIONS = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang'];

    // RELATIONSHIPS
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function returnedToUser()
    {
        return $this->belongsTo(User::class, 'returned_to');
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'approved', 'dipinjam']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'dipinjam')
            ->whereDate('expected_return_date', '<', now());
    }
}
