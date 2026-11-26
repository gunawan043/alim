<?php

namespace App\Services;

use App\Models\GtkAdditionalTask;
use App\Models\GtkEmployment;
use App\Models\OtherTeacherTask;
use App\Models\TahfidzCoordinator;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupTeacher;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\Schema;

/**
 * Kewenangan modul Tahfidz — dua lapis:
 * - Sekolah: koordinator tahfidz mengelola halaqah dan operasional harian.
 * - Departemen Tahfidz: kurikulum, QC, ujian, sertifikasi, lintas sekolah.
 *
 * Home base kepegawaian TIDAK sama dengan penugasan halaqah. Guru
 * departemen hanya boleh input setoran jika punya penugasan halaqah.
 */
class TahfidzAccess
{
    public const DEPARTMENT_CODE = 'DEP-TAH';

    public function isSuperAdmin(User $user): bool
    {
        return (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin());
    }

    public function isDepartment(User $user): bool
    {
        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all()
            : [];

        foreach ($roles as $role) {
            if (str_contains($role, 'departemen tahfidz')) {
                return true;
            }
        }

        if (str_contains($this->jabatan($user), 'departemen tahfidz')) {
            return true;
        }

        return false;
    }

    public function isSchoolCoordinator(User $user, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        // Tugas tambahan bisa tersimpan di dua tempat: gtk_additional_tasks
        // (SK kepegawaian) dan other_teacher_tasks (penugasan dari matriks SK,
        // halaman Other Teacher Tasks). Keduanya harus diakui — koordinator
        // yang tercatat di halaman tugas lainnya dulu tidak bisa mengelola
        // halaqah karena hanya satu tabel yang dibaca.
        if ($this->hasCoordinatorTask($user, $schoolId)) {
            return true;
        }

        if (! Schema::hasTable('tahfidz_coordinators')) {
            return false;
        }

        return TahfidzCoordinator::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->exists();
    }

    /**
     * Apakah pengguna memegang tugas tambahan koordinator tahfidz yang berlaku.
     *
     * Sumber yang dibaca:
     * 1. gtk_additional_tasks — SK kepegawaian; masa berlaku dari tst.
     * 2. other_teacher_tasks — penugasan dari matriks SK; berlaku selama
     *    barisnya aktif dan (bila diisi) tahun ajarannya masih berjalan.
     *
     * Dibaca langsung dari tabel (bukan lewat relasi) agar tugas yang masih
     * berlaku tetapi tidak punya tanggal berakhir tetap dikenali. Nama tugas
     * dicocokkan longgar: "Koordinator Guru Tahfidz", "Koordinator Tahfidz",
     * dan "Koordinator Tahfiz" sama-sama diterima.
     */
    public function hasCoordinatorTask(User $user, ?string $schoolId = null): bool
    {
        if ($this->hasCoordinatorTaskInSk($user)) {
            return true;
        }

        return $this->hasCoordinatorTaskInOtherTasks($user, $schoolId);
    }

    private function hasCoordinatorTaskInSk(User $user): bool
    {
        if (! Schema::hasTable('gtk_additional_tasks')) {
            return false;
        }

        return GtkAdditionalTask::query()
            ->where('user_id', $user->id)
            ->where(fn ($q) => $q->whereNull('tst')->orWhereDate('tst', '>=', today()))
            ->pluck('nama_tugas')
            ->contains($this->isCoordinatorTaskName(...));
    }

    private function hasCoordinatorTaskInOtherTasks(User $user, ?string $schoolId): bool
    {
        if (! Schema::hasTable('other_teacher_tasks')) {
            return false;
        }

        return OtherTeacherTask::query()
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('task_name')
            ->contains($this->isCoordinatorTaskName(...));
    }

    public function isCoordinatorTaskName(?string $name): bool
    {
        $name = strtolower(trim((string) $name));

        return str_contains($name, 'koordinator')
            && (str_contains($name, 'tahfidz') || str_contains($name, 'tahfiz'));
    }

    /**
     * Nama tugas koordinator tahfidz sudah diakui, baik ejaan "tahfidz"
     * maupun "tahfiz". Dipakai juga oleh query yang mencari koordinator.
     *
     * @return \Closure(\Illuminate\Database\Eloquent\Builder): void
     */
    public function coordinatorTaskLike(): \Closure
    {
        return fn ($q) => $q->where('nama_tugas', 'like', '%Koordinator%')
            ->where(fn ($inner) => $inner->where('nama_tugas', 'like', '%Tahfidz%')
                ->orWhere('nama_tugas', 'like', '%Tahfiz%'));
    }

    public function isSchoolLeader(User $user): bool
    {
        return \App\Support\UserJabatan::hasAnyLoose($user, [
            'kepala satuan pendidikan',
            'wakil kepala satuan pendidikan',
            'kepala sekolah',
            'wakil kepala sekolah',
            'waka',
            'koordinator kurikulum',
        ]);
    }

    public function canViewAllSchools(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isDepartment($user);
    }

    public function canManageCurriculum(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isDepartment($user);
    }

    public function canManageGroup(User $user, TahfidzGroup $group): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->isDepartment($user) && $this->isDepartmentGroup($group)) {
            return true;
        }

        return $this->isSchoolCoordinator($user, $group->school_id, $group->academic_year_id);
    }

    public function canViewGroup(User $user, TahfidzGroup $group): bool
    {
        if ($this->canViewAllSchools($user)) {
            return true;
        }

        if ($this->isSchoolCoordinator($user, $group->school_id, $group->academic_year_id)) {
            return true;
        }

        if ($this->isSchoolLeader($user) && $this->hasSchoolAssignment($user, $group->school_id)) {
            return true;
        }

        if ((string) $group->teacher_id === (string) $user->id
            || (string) $group->coordinator_id === (string) $user->id) {
            return true;
        }

        return $this->isAssignedTeacher($user, $group);
    }

    /**
     * Input setoran hanya untuk guru halaqah, koordinator sekolah, atau
     * departemen yang memang diberi penugasan mengajar.
     */
    public function isTahfidzTeacher(User $user): bool
    {
        $jabatan = $this->jabatan($user);
        if (str_contains($jabatan, 'guru tahfidz') || str_contains($jabatan, 'guru tahfiz')
            || str_contains($jabatan, 'koordinator guru tahfidz') || str_contains($jabatan, 'koordinator guru tahfiz')) {
            return true;
        }

        // Penugasan halaqah (Musyrif Tahfidz / Koordinator Halaqah) sering
        // dicatat di Other Teacher Tasks, bukan sebagai jabatan.
        if ($this->hasCoordinatorTask($user) || $this->hasTahfidzTaskInOtherTasks($user)) {
            return true;
        }

        if (! Schema::hasTable('tahfidz_groups')) {
            return false;
        }

        return TahfidzGroup::query()
            ->where(fn ($q) => $q->where('teacher_id', $user->id)->orWhere('coordinator_id', $user->id))
            ->exists()
            || TahfidzGroupTeacher::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists();
    }

    private function hasTahfidzTaskInOtherTasks(User $user): bool
    {
        if (! Schema::hasTable('other_teacher_tasks')) {
            return false;
        }

        return OtherTeacherTask::query()
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->pluck('task_name')
            ->contains(fn ($name) => (bool) preg_match('/tahfi[dz]/i', (string) $name));
    }

    public function canInputSetoran(User $user, TahfidzGroup $group): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->isSchoolCoordinator($user, $group->school_id, $group->academic_year_id)) {
            return true;
        }

        if ((string) $group->teacher_id === (string) $user->id
            || (string) $group->coordinator_id === (string) $user->id) {
            return true;
        }

        if ($this->canSubstituteSetoran($user, $group)) {
            return true;
        }

        return TahfidzGroupTeacher::query()
            ->where('tahfidz_group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('can_input_setoran', true)
            ->exists();
    }

    /**
     * Guru Tahfidz di sekolah yang sama dapat mengisi setoran saat
     * menggantikan guru halaqah yang berhalangan.
     */
    public function canSubstituteSetoran(User $user, TahfidzGroup $group): bool
    {
        return $this->isTahfidzTeacher($user)
            && $this->hasSchoolAssignment($user, $group->school_id);
    }

    public function canVerifySetoran(User $user, TahfidzGroup $group): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isDepartment($user)
            || $this->isSchoolCoordinator($user, $group->school_id, $group->academic_year_id);
    }

    public function departmentWorkUnit(): ?WorkUnit
    {
        return WorkUnit::query()->where('code', self::DEPARTMENT_CODE)->first();
    }

    /**
     * Koordinator tahfidz sebuah satuan pendidikan, dipakai untuk mengisi
     * kolom coordinator_id secara otomatis saat membuat halaqah.
     *
     * Urutan sumber: tabel tahfidz_coordinators, lalu tugas tambahan
     * "Koordinator Guru Tahfidz" pada GTK sekolah tersebut — baik dari
     * gtk_additional_tasks maupun other_teacher_tasks.
     */
    public function coordinatorForSchool(?string $schoolId, ?string $academicYearId = null): ?User
    {
        if (! $schoolId) {
            return null;
        }

        if (Schema::hasTable('tahfidz_coordinators')) {
            $registered = TahfidzCoordinator::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->orderByDesc('created_at')
                ->first();

            if ($registered?->user_id) {
                return User::find($registered->user_id);
            }
        }

        $fromSk = User::query()
            ->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->whereHas('tugasTambahan', $this->coordinatorTaskLike())
            ->orderBy('name')
            ->first();

        if ($fromSk) {
            return $fromSk;
        }

        // Penugasan dari matriks SK tidak selalu punya baris kepegawaian,
        // jadi sekolah dicocokkan lewat kolom school_id pada tugasnya.
        if (! Schema::hasTable('other_teacher_tasks')) {
            return null;
        }

        $teacherId = OtherTeacherTask::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('task_name', 'like', '%Koordinator%')
                ->where(fn ($inner) => $inner->where('task_name', 'like', '%Tahfidz%')
                    ->orWhere('task_name', 'like', '%Tahfiz%')))
            ->value('teacher_id');

        return $teacherId ? User::find($teacherId) : null;
    }

    /**
     * Satuan pendidikan tempat pengguna bertugas.
     *
     * Kepegawaian dengan school_id kosong (hanya penempatan unit kerja)
     * banyak terjadi, sehingga unit kerjanya diterjemahkan ke sekolah
     * sebelum jatuh ke daftar penempatan.
     */
    public function schoolIdFor(User $user): ?string
    {
        return app(\App\Services\SchoolGroupService::class)->getUserSchool($user)?->id;
    }

    private function isDepartmentGroup(TahfidzGroup $group): bool
    {
        $department = $this->departmentWorkUnit();

        return $department && (string) $group->work_unit_id === (string) $department->id;
    }

    private function isAssignedTeacher(User $user, TahfidzGroup $group): bool
    {
        return TahfidzGroupTeacher::query()
            ->where('tahfidz_group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    private function hasSchoolAssignment(User $user, ?string $schoolId): bool
    {
        if (! $schoolId) {
            return false;
        }

        return GtkEmployment::query()
            ->where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->exists();
    }

    private function jabatan(User $user): string
    {
        // Gabungkan semua sumber jabatan/tugas: kepegawaian, penempatan unit,
        // penugasan struktural, dan tugas tambahan. Waka yang tercatat sebagai
        // tugas tambahan tetap dikenali sebagai pimpinan sekolah.
        return implode(' ', \App\Support\UserJabatan::candidates($user));
    }
}
