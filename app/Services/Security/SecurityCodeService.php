<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\DB;

/**
 * Pembuat kode dokumen modul keamanan.
 *
 * Format: {PREFIX}-{YYYYMMDD}-{urut 4 digit}, mis. TAMU-20261103-0007.
 * Urutan dihitung per prefix per hari, sehingga mudah dibaca petugas.
 */
class SecurityCodeService
{
    public function next(string $table, string $prefix): string
    {
        $date = now()->format('Ymd');
        $base = $prefix.'-'.$date.'-';

        $last = DB::table($table)
            ->where('code', 'like', $base.'%')
            ->orderByDesc('code')
            ->value('code');

        $sequence = 1;

        if ($last) {
            $sequence = (int) substr($last, -4) + 1;
        }

        return $base.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function guest(): string
    {
        return $this->next('security_guest_logs', 'TAMU');
    }

    public function vehicle(): string
    {
        return $this->next('security_vehicle_logs', 'KND');
    }

    public function patrol(): string
    {
        return $this->next('security_patrol_schedules', 'PTR');
    }

    public function incident(): string
    {
        return $this->next('security_incidents', 'INS');
    }
}
