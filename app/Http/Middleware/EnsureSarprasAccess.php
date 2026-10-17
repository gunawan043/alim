<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSarprasAccess
{
    /**
     * Cek apakah user berhak akses modul Sarpras/URT.
     * Aturan:
     *   - Super Admin / System Admin → bypass
     *   - Role: Admin Sarpras, Admin Tata Usaha, Unit Rumah Tangga
     *   - Jabatan: Kepala URT, Koor Sarpras, Teknisi, Kebersihan, Driver
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super Admin bypass
        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return $next($request);
        }

        // Cek permission (kalau ada)
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('sarpras_access')) {
            return $next($request);
        }

        // Cek role
        $allowedRoles = [
            'Admin Sarpras',
            'Admin Tata Usaha',
            'Unit Rumah Tangga',
        ];
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($allowedRoles)) {
            return $next($request);
        }

        // Cek jabatan
        $allowedJabatans = [
            'Kepala Unit Rumah Tangga',
            'Koordinator Sarana Prasarana',
            'Koordinator Sarpras',
            'Teknisi Maintenance',
            'Teknisi',
            'Petugas Kebersihan',
            'Driver',
            'Pengemudi',
        ];
        $jabatan = method_exists($user, 'jabatan')
            ? trim($user->jabatan->nama ?? $user->jabatan ?? '')
            : '';

        if (in_array($jabatan, $allowedJabatans, true)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke modul Sarpras / Unit Rumah Tangga.');
    }
}