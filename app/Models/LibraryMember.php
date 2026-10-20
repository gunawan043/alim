<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryMember extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    protected $table = 'library_members';

    protected $fillable = [
        'library_unit_id', 'student_id', 'user_id', 'member_number', 'member_type',
        'status', 'joined_at', 'ended_at', 'is_primary', 'notes',
    ];

    protected $casts = ['joined_at' => 'date', 'ended_at' => 'date', 'is_primary' => 'boolean'];

    public function unit(): BelongsTo { return $this->belongsTo(LibraryUnit::class, 'library_unit_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function cards(): HasMany { return $this->hasMany(LibraryMemberCard::class); }
    public function loans(): HasMany { return $this->hasMany(LibraryLoan::class); }
    public function visits(): HasMany { return $this->hasMany(LibraryVisit::class); }

    public function getDisplayNameAttribute(): string
    {
        return $this->student?->name ?? $this->user?->name ?? 'Anggota';
    }
}
