<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumasGuestVisit extends Model
{
    use HasUuids;

    protected $table = 'humas_guest_visits';

    protected $fillable = [
        'school_id', 'visitor_name', 'institution', 'phone', 'person_to_meet',
        'purpose', 'status', 'check_in_at', 'check_out_at', 'officer_id', 'notes',
    ];

    protected $casts = ['check_in_at' => 'datetime', 'check_out_at' => 'datetime'];

    public function officer(): BelongsTo { return $this->belongsTo(User::class, 'officer_id'); }
}
