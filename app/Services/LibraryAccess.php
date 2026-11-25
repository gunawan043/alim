<?php

namespace App\Services;

use App\Models\GtkAdditionalTask;
use App\Models\GtkWorkUnit;
use App\Models\LibraryStaff;
use App\Models\StudentOperatorAssignment;
use App\Models\LibraryUnit;
use App\Models\School;
use App\Models\User;

class LibraryAccess
{
    /** Kata kunci jabatan/tugas yang membuka modul perpustakaan. */
    private const LIBRARY_DUTY_KEYS = ['perpustakaan', 'pustakawan'];

    public function isSuperAdmin(User $user): bool
    {
        return (bool) $user->is_system_admin || $user->hasRole('Super Admin');
    }

    public function hasLibraryRole(User $user): bool
    {
        return $user->hasRole('Perpustakaan') || $this->isSuperAdmin($user);
    }

    public function isStudentOperator(User $user): bool
    {
        return $user->hasRole('Operator Perpustakaan Santri');
    }

    /**
     * Unit perpustakaan yang boleh diakses user.
     *
     * Scope mengikuti SEKOLAH TEMPAT USER BERTUGAS, bukan letak penempatan
     * di work unit perpustakaan. Guru SD IT Putri yang menjadi koordinator
     * perpustakaan membuka perpustakaan yang melayani SD IT Putri — bukan
     * dipindahkan ke unit lain.
     *
     * Urutan:
     *  1. Operator perpustakaan santri → hanya unit yang ditugaskan padanya.
     *  2. Super admin                  → semua unit.
     *  3. Penugasan eksplisit (library_staff) → mempersempit ke unit itu.
     *  4. Sekolah tempat bertugas      → unit yang melayani sekolah tersebut.
     *
     * Bila sekolah tidak terpetakan ke unit mana pun, hasilnya kosong —
     * bukan seluruh unit, supaya tidak ada akses yang meluber.
     */
    public function unitsFor(User $user)
    {
        $query = LibraryUnit::query()->active();

        if ($this->isStudentOperator($user)) {
            $unitIds = StudentOperatorAssignment::query()
                ->where('user_id', $user->id)
                ->where('operator_type', 'library')
                ->where('is_active', true)
                ->whereNotNull('library_unit_id')
                ->pluck('library_unit_id');

            return $query->whereIn('id', $unitIds)->orderBy('name')->get();
        }

        if ($this->isSuperAdmin($user)) {
            return $query->orderBy('name')->get();
        }

        $assignedIds = LibraryStaff::query()
            ->where('user_id', $user->id)
            ->active()
            ->pluck('library_unit_id');

        if ($assignedIds->isNotEmpty()) {
            return $query->whereIn('id', $assignedIds)->orderBy('name')->get();
        }

        if (! $this->hasLibraryDuty($user)) {
            return $query->whereRaw('1 = 0')->get();
        }

        $codes = $this->unitCodesForSchools($this->schoolIdsOf($user));

        if ($codes === []) {
            return $query->whereRaw('1 = 0')->get();
        }

        return $query->whereIn('code', $codes)->orderBy('name')->get();
    }

    /**
     * Sekolah tempat user bertugas.
     *
     * Diambil dari penempatan GTK → work unit → sekolah, serta dari sekolah
     * pada data kepegawaian. Satu guru bisa tercatat di lebih dari satu
     * tempat; semuanya dikumpulkan.
     *
     * @return array<int, string>
     */
    public function schoolIdsOf(User $user): array
    {
        $ids = [];

        if ($user->employment?->school_id) {
            $ids[] = (string) $user->employment->school_id;
        }

        $workUnitIds = GtkWorkUnit::query()
            ->where('user_id', $user->id)
            ->pluck('work_unit_id')
            ->filter()
            ->unique();

        if ($workUnitIds->isNotEmpty()) {
            foreach (School::query()->whereIn('work_unit_id', $workUnitIds)->pluck('id') as $id) {
                $ids[] = (string) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Kode unit perpustakaan yang melayani sekolah-sekolah tersebut.
     *
     * @param  array<int, string>  $schoolIds
     * @return array<int, string>
     */
    public function unitCodesForSchools(array $schoolIds): array
    {
        if ($schoolIds === []) {
            return [];
        }

        $names = School::query()->whereIn('id', $schoolIds)->pluck('name');
        $codes = [];

        // Satu sekolah bisa cocok dengan beberapa unit (SD IT Putri cocok
        // dengan Perpustakaan Putri dan Perpustakaan SD). Pola TERPANJANG
        // menang, karena pola yang lebih panjang berarti lebih spesifik:
        // "SD IT Putri" mengalahkan "Putri" polos.
        $pemetaan = (array) config('perpustakaan.units', []);

        foreach ($names as $name) {
            $terbaik = null;
            $panjangTerbaik = 0;

            foreach ($pemetaan as $code => $patterns) {
                foreach ($patterns as $pattern) {
                    $panjang = mb_strlen((string) $pattern);

                    if ($panjang > $panjangTerbaik
                        && mb_stripos((string) $name, (string) $pattern) !== false) {
                        $terbaik = $code;
                        $panjangTerbaik = $panjang;
                    }
                }
            }

            if ($terbaik !== null) {
                $codes[] = $terbaik;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Sekolah yang menjadi hak user pada unit tertentu.
     *
     * Dipakai untuk menyaring data santri: pada unit yang dipakai bersama
     * (mis. Perpustakaan SD melayani SD Putra dan SD Putri), koordinator dari
     * SD Putri hanya melihat santri SD Putri. Super admin dan operator santri
     * tidak disaring.
     *
     * @return array<int, string>|null  null berarti tanpa penyaringan.
     */
    public function schoolIdsForUnit(User $user, ?LibraryUnit $unit): ?array
    {
        if ($unit === null || $this->isSuperAdmin($user) || $this->isStudentOperator($user)) {
            return null;
        }

        $sekolahUser = $this->schoolIdsOf($user);
        $patterns = (array) (config('perpustakaan.units', [])[$unit->code] ?? []);

        if ($sekolahUser === [] || $patterns === []) {
            return [];
        }

        // Sekolah milik user yang memang dilayani unit ini.
        $names = School::query()->whereIn('id', $sekolahUser)->pluck('name', 'id');
        $hasil = [];

        foreach ($names as $id => $name) {
            foreach ($patterns as $pattern) {
                if (mb_stripos((string) $name, (string) $pattern) !== false) {
                    $hasil[] = (string) $id;
                    break;
                }
            }
        }

        return array_values(array_unique($hasil));
    }

    /**
     * User memegang tugas/jabatan perpustakaan.
     *
     * Hanya mereka yang boleh masuk. Berada di work unit perpustakaan saja
     * TIDAK cukup: satu work unit sekolah dapat berisi puluhan guru, dan
     * semuanya akan ikut membuka perpustakaan bila syaratnya hanya work unit.
     */
    public function hasLibraryDuty(User $user): bool
    {
        // Operator santri hanya boleh mengoperasikan sirkulasi, bukan
        // mengelola katalog. Peran itu ditangani cabang tersendiri.
        if ($this->isStudentOperator($user)) {
            return false;
        }

        if ($this->hasLibraryRole($user)) {
            return true;
        }

        $names = $this->dutyNames($user);

        foreach (self::LIBRARY_DUTY_KEYS as $key) {
            foreach ($names as $name) {
                if (str_contains($name, $key)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Work unit tempat tugas perpustakaan user tercatat.
     *
     * Diambil dari tugas tambahan aktif (gtk_additional_tasks.work_unit_id).
     * Bila tugas tambahan tidak mencantumkan work unit, dipakai penempatan
     * GTK (gtk_work_unit). Inilah yang memisahkan perpustakaan SD, putra,
     * dan putri tanpa perlu tabel penugasan terpisah.
     *
     * @return array<int, string>
     */
    public function dutyWorkUnitIds(User $user): array
    {
        $taskIds = GtkAdditionalTask::query()
            ->where('user_id', $user->id)
            ->whereNotNull('work_unit_id')
            ->where(function ($q) {
                $q->whereNull('tst')->orWhereDate('tst', '>=', today());
            })
            ->where(function ($q) {
                foreach (self::LIBRARY_DUTY_KEYS as $key) {
                    $q->orWhereRaw('LOWER(nama_tugas) LIKE ?', ['%'.$key.'%']);
                }
            })
            ->pluck('work_unit_id');

        if ($taskIds->isNotEmpty()) {
            return $taskIds->map(fn ($id) => (string) $id)->unique()->values()->all();
        }

        return GtkWorkUnit::query()
            ->where('user_id', $user->id)
            ->pluck('work_unit_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Nama jabatan dan tugas tambahan user, sudah dinormalkan ke huruf kecil.
     *
     * @return array<int, string>
     */
    private function dutyNames(User $user): array
    {
        $names = [];

        foreach ([$user->employment?->jabatan] as $jabatan) {
            if (is_string($jabatan) && trim($jabatan) !== '') {
                $names[] = mb_strtolower(trim($jabatan));
            }
        }

        $tugasAktif = GtkAdditionalTask::query()
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('tst')->orWhereDate('tst', '>=', today());
            })
            ->pluck('nama_tugas');

        foreach ($tugasAktif as $tugas) {
            if (is_string($tugas) && trim($tugas) !== '') {
                $names[] = mb_strtolower(trim($tugas));
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Terapkan penyaringan santri per sekolah pada query anggota.
     *
     * Pada unit yang dipakai bersama (Perpustakaan SD melayani SD Putra dan
     * SD Putri), koordinator dari SD Putri hanya melihat santri SD Putri.
     * Super admin dan operator santri tidak disaring.
     */
    public function scopeMembersToSchools($query, User $user, ?LibraryUnit $unit)
    {
        $schoolIds = $this->schoolIdsForUnit($user, $unit);

        if ($schoolIds === null) {
            return $query;
        }

        if ($schoolIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($schoolIds) {
            $q->whereHas('student', fn ($st) => $st->whereIn('school_id', $schoolIds))
                // Anggota GTK tidak terikat sekolah santri; biarkan tampil
                // agar koordinator tetap dapat mengelola keanggotaannya.
                ->orWhere('member_type', 'gtk');
        });
    }

    public function canAccessUnit(User $user, ?LibraryUnit $unit): bool
    {
        return $unit !== null && $this->unitsFor($user)->contains('id', $unit->id);
    }

    /**
     * User berhak masuk modul perpustakaan tetapi belum ditugaskan di unit
     * mana pun. Dipakai halaman unit untuk menampilkan pesan yang menjelaskan
     * apa yang harus dilakukan, bukan sekadar daftar kosong.
     */
    public function hasNoUnitAssigned(User $user): bool
    {
        return $this->canManage($user) && $this->unitsFor($user)->isEmpty();
    }

    public function canManage(User $user): bool
    {
        return $this->hasLibraryDuty($user);
    }

    public function canOperate(User $user, ?LibraryUnit $unit = null): bool
    {
        if ($this->canManage($user)) {
            return $unit === null || $this->canAccessUnit($user, $unit);
        }

        if (! $this->isStudentOperator($user)) {
            return false;
        }

        return StudentOperatorAssignment::query()
            ->where('user_id', $user->id)
            ->where('operator_type', 'library')
            ->where('is_active', true)
            ->when($unit, fn ($q) => $q->where('library_unit_id', $unit->id))
            ->exists();
    }

    public function canManageUnit(User $user, ?LibraryUnit $unit): bool
    {
        return $this->canManage($user) && $this->canAccessUnit($user, $unit);
    }
}
