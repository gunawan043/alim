<?php

namespace App\Http\Middleware;

use App\Models\Dormitory;
use App\Models\RoomSupervisor;
use App\Models\StudyGroup;
use App\Models\WorkUnitSarprasOfficer;
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

        // Halaman personal/read-only boleh diakses semua GTK yang login.
        // Hak tulis tetap diperiksa oleh controller masing-masing.
        $routeName = (string) $request->route()?->getName();
        foreach ([
            'sarpras.user.',
            'sarpras.camera.',
            'sarpras.qr.scanner',
            'sarpras.qr.lookup',
            'sarpras.assets.passport',
            'sarpras.assets.scan',
        ] as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        // Cek permission (kalau ada). Jangan 500 bila permission belum di-seed.
        try {
            if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('sarpras_access')) {
                return $next($request);
            }
        } catch (\Throwable $e) {
            // Permission belum tersedia pada instalasi ini — lanjut ke pemeriksaan role/jabatan.
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

        // Kepala satuan kerja berwenang menunjuk petugas sarpras unitnya.
        if (method_exists($user, 'isWorkUnitHead') && $user->isWorkUnitHead()) {
            return $next($request);
        }

        // Petugas Sarpras Satuan Kerja yang ditunjuk kepala unit / URT.
        if (WorkUnitSarprasOfficer::query()->active()->where('user_id', $user->id)->exists()) {
            return $next($request);
        }

        // Wali kelas / wali kamar mendata aset pada ruang yang menjadi tanggung jawabnya.
        $isHomeroom = StudyGroup::query()->where('homeroom_teacher_id', $user->id)->exists();
        $isRoomSupervisor = RoomSupervisor::query()->active()->where('user_id', $user->id)->exists();
        $isDormitoryHead = Dormitory::query()->where('head_id', $user->id)->exists();

        if ($isHomeroom || $isRoomSupervisor || $isDormitoryHead) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke modul Sarpras / Unit Rumah Tangga.');
    }
}