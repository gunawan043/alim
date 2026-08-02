<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GtkPension extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'planned_pension_date',
        'pension_type',
        'pension_letter_no',
        'pension_letter_date',
        'pension_status',
        'benefit_amount',
        'benefit_notes',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'planned_pension_date' => 'date',
        'pension_letter_date' => 'date',
        'benefit_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gtkProfile()
    {
        return $this->hasOneThrough(
            GtkProfile::class,
            User::class,
            'id',
            'user_id_uuid',
            'user_id',
            'id'
        );
    }

    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'pending' => 'Pending',
            'approved' => 'Disetujui',
            'completed' => 'Selesai',
            'cancelled' => 'Batal',
            default => ucfirst($status),
        };
    }

    public static function getTypeLabel(string $type): string
    {
        return match ($type) {
            'normal' => 'Pensi Normal',
            'dini' => 'Pensi Dini',
            'cacat' => 'Pensi Cacat',
            'janda' => 'Pensi Janda/Duda',
            default => ucfirst($type),
        };
    }
}
