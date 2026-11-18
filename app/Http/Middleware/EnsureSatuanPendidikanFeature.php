<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use App\Models\GtkProfile;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\SupervisiPengaturan;
use App\Support\UserJabatan;
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

        $hasTugas = fn (string $needle): bool => $tugas->contains(
            fn ($name) => str_contains($name, strtoupper($needle))
        );

        // Jabatan dibaca dari SEMUA sumber (kepegawaian, penempatan unit,
        // penugasan struktural, tugas tambahan) + alias "Waka". Tanpa ini,
        // Waka yang tercatat sebagai tugas tambahan selalu ditolak 403.
        $isKepala = UserJabatan::hasAnyLoose($user, ['kepala satuan pendidikan', 'kepala sekolah'])
            || School::where('principal_user_id', $user->id)->where('is_active', true)->exists();
        $isWakil = UserJabatan::hasAnyLoose($user, [
            'wakil kepala satuan pendidikan',
            'wakil kepala sekolah',
            'waka',
        ]);
        $isKepalaTu = UserJabatan::hasAnyLoose($user, [
            'kepala tata usaha',
            'kepala staf tata usaha',
            'kepala tu sekolah',
        ]);
        $isTu = UserJabatan::hasAnyLoose($user, ['tata usaha', 'staf tata usaha', 'tu sekolah']);
        $isKoorKurikulum = UserJabatan::hasAnyLoose($user, ['koordinator kurikulum'])
            || $hasTugas('KOORDINATOR KURIKULUM');
        $isTimKurikulum = $hasTugas('TIM KURIKULUM');
        $isKoorEkskul = UserJabatan::hasAnyLoose($user, ['koordinator ekstrakurikuler'])
            || $hasTugas('KOORDINATOR EKSTRAKURIKULER');
        $isKoorKesiswaan = UserJabatan::hasAnyLoose($user, ['koordinator kesiswaan'])
            || $hasTugas('KOORDINATOR KESISWAAN');
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
            'ekstrakurikuler' => $isKepala || $isWakil || $isKoorEkskul || $isKoorKesiswaan || $isPembinaEkskul || $isKepalaTu || $isTu,
            'surat' => $isKepala || $isWakil || $isKepalaTu || $isTu,
            default => false,
        };

        abort_unless($allowed, 403, 'Jabatan Anda tidak memiliki akses ke menu ini.');

        return $next($request);
    }
}
