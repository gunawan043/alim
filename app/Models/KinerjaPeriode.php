<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KinerjaPeriode extends Model
{
    use HasUuids;
    protected $table = 'kinerja_periode';

    protected $fillable = ['nama', 'tanggal_mulai', 'tanggal_selesai', 'status', 'notes'];

    protected $casts = ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];

    public function penilaian(): HasMany
    {
        return $this->hasMany(KinerjaPenilaian::class, 'kinerja_periode_id');
    }

    public function rewardPunishments(): HasMany
    {
        return $this->hasMany(KinerjaRewardPunishment::class, 'kinerja_periode_id');
    }

    public function scopeAktif($q) { return $q->where('status', 'aktif'); }
    public function scopeSelesai($q) { return $q->where('status', 'selesai'); }
}