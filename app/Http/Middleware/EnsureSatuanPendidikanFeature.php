<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use App\Models\GtkProfile;
use App\Models\OtherTeacherTask;
use App\Models\SupervisiPengaturan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi fitur administratif Satuan Pendidikan berdasarkan jabatan/tugas.
 *
 * Role "Satuan Pendidikan" dipakai semua guru. Menu sidebar memang sudah
 * difilter, tetapi akses URL langsung juga harus dijaga di server.
 */
class EnsureSatuanPendidikanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        if ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return $next($request);
        }

        $jabatan = strtoupper(trim((string) ($user->employment?->jabatan ?? '')));

        $tugas = collect();
        if (method_exists($user, 'tugasTambahan')) {
            $tugas = $tugas->merge($user->tugasTambahan()->pluck('nama_tugas'));
        }
        if (class_exists(OtherTeacherTask::class)) {
            $tugas = $tugas->merge(
                OtherTeacherTask::query()
                    ->where('teacher_id', $user->id)
                    ->where('is_active', 1)
                    ->pluck('task_name')
            );
        }

        $tugas = $tugas
            ->filter()
            ->map(fn ($name) => strtoupper(trim((string) $name)))
            ->unique()
            ->values();

        $hasJabatan = fn (string $needle): bool => str_contains($jabatan, strtoupper($needle));
        $hasTugas = fn (string $needle): bool => $tugas->contains(strtoupper($needle));

        $isKepala = $hasJabatan('KEPALA SATUAN PENDIDIKAN');
        $isWakil = $hasJabatan('WAKIL KEPALA SATUAN PENDIDIKAN');
        $isKepalaTu = $hasJabatan('KEPALA TATA USAHA');
        $isTu = $hasJabatan('TATA USAHA');
        $isKoorKurikulum = $hasJabatan('KOORDINATOR KURIKULUM') || $hasTugas('KOORDINATOR KURIKULUM');
        $isTimKurikulum = $hasTugas('TIM KURIKULUM');
        $isKoorEkskul = $hasJabatan('KOORDINATOR EKSTRAKURIKULER') || $hasTugas('KOORDINATOR EKSTRAKURIKULER');
        $isPembinaEkskul = $hasTugas('PEMBINA EKSTRAKURIKULER');

        $schoolId = $request->attributes->get('schoolContextId');
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $profileId = GtkProfile::where('user_id', $user->id)->value('id');
        $isConfiguredSupervisor = $profileId && $schoolId
            && SupervisiPengaturan::query()
                ->where('school_id', $schoolId)
                ->where('supervisor_gtk_id', $profileId)
                ->where('is_active', true)
                ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
                ->exists();

        $supervisiAdmin = $isKepala || $isWakil || $isKoorKurikulum || $isTimKurikulum || $isKepalaTu || $isTu;

        $allowed = match ($feature) {
            'supervisi' => $supervisiAdmin || $isConfiguredSupervisor,
            'supervisi-admin' => $supervisiAdmin,
            'ekstrakurikuler' => $isKepala || $isWakil || $isKoorEkskul || $isPembinaEkskul || $isKepalaTu || $isTu,
            'surat' => $isKepala || $isWakil || $isKepalaTu || $isTu,
            default => false,
        };

        abort_unless($allowed, 403, 'Jabatan Anda tidak memiliki akses ke menu ini.');

        return $next($request);
    }
}
