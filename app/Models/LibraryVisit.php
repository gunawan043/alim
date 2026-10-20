<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryVisit extends Model
{
    use HasUuids;

    protected $table = 'library_visits';

    protected $fillable = [
        'library_unit_id', 'library_member_id', 'visitor_name', 'visitor_type',
        'class_snapshot', 'visit_date', 'purpose', 'officer_id', 'notes',
    ];

    protected $casts = ['visit_date' => 'date'];

    public function unit(): BelongsTo { return $this->belongsTo(LibraryUnit::class, 'library_unit_id'); }
    public function member(): BelongsTo { return $this->belongsTo(LibraryMember::class, 'library_member_id'); }
    public function officer(): BelongsTo { return $this->belongsTo(User::class, 'officer_id'); }
}
