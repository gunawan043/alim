<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityTeamMember extends SecurityModel
{
    protected $table = 'security_team_members';

    public const POSITION_OPTIONS = [
        'komandan' => 'Komandan Regu',
        'anggota' => 'Anggota',
    ];

    protected $fillable = ['team_id', 'user_id', 'position', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(SecurityTeam::class, 'team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Penugasan yang berlaku hari ini.
     *
     * Memakai whereDate agar perbandingan tetap benar di SQLite (lihat catatan
     * proyek: kolom date disimpan sebagai 'Y-m-d H:i:s').
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhereDate('start_date', '<=', today());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            });
    }
}
