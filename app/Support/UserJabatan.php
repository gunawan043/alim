<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Dormitory;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\StructuralAssignment;
use App\Models\User;

/**
 * Resolver jabatan user yang tangguh.
 *
 * Sumber jabatan memang ambigu di data existing: bisa dari
 * `gtk_employments.jabatan` (string), `gtk_employments.jabatan_id`
 * (relasi `jabatanRel`), penempatan per satuan kerja
 * (`gtk_work_unit.jabatan` / `position_id`), penugasan struktural, dan
 * tugas tambahan (`gtk_additional_tasks` / `other_teacher_tasks`).
 *
 * Satu GTK dapat merangkap beberapa peran sekaligus (mis. Waka Kurikulum,
 * Wali Kelas, dan Guru Mapel). Karena itu semua sumber dibaca, bukan hanya
 * jabatan utama. Pola ini menyamakan cara baca dengan sidebar dan gate
 * route sehingga menu yang tampil tidak lagi ditolak 403 hanya karena
 * jabatannya tersimpan di tabel yang berbeda.
 */
final class UserJabatan
{
    /**
     * Semua kandidat nama jabatan (lowercase, trimmed) milik user,
     * termasuk alias baku agar varian penulisan tetap cocok.
     *
     * @return list<string>
     */
    public static function candidates(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $raw = [];

        // 1. Kepegawaian utama + seluruh baris kepegawaian.
        try {
            $employment = $user->employment;

            if ($employment?->jabatanRel?->nama) {
                $raw[] = $employment->jabatanRel->nama;
            }

            if ($employment?->jabatan) {
                $raw[] = $employment->jabatan;
            }

            if (method_exists($user, 'employments')) {
                foreach ($user->employments as $row) {
                    if ($row?->jabatanRel?->nama) {
                        $raw[] = $row->jabatanRel->nama;
                    }

                    if ($row?->jabatan) {
                        $raw[] = $row->jabatan;
                    }
                }
            }
        } catch (\Throwable) {
            // Relasi kepegawaian tidak tersedia pada konteks tertentu.
        }

        // 2. Penempatan per satuan kerja (satu GTK bisa punya beberapa unit).
        try {
            $placements = method_exists($user, 'gtkWorkUnits')
                ? $user->gtkWorkUnits()->with('position')->get()
                : collect();

            foreach ($placements as $placement) {
                if ($placement?->position?->name) {
                    $raw[] = $placement->position->name;
                }

                if ($placement?->jabatan) {
                    $raw[] = $placement->jabatan;
                }
            }
        } catch (\Throwable) {
            // Tabel penempatan belum ada (mis. saat instalasi awal).
        }

        // 3. Penugasan struktural aktif (SK struktural per tahun ajaran).
        try {
            $assignments = StructuralAssignment::query()
                ->with('position')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->get();

            foreach ($assignments as $assignment) {
                if ($assignment?->position?->name) {
                    $raw[] = $assignment->position->name;
                }
            }
        } catch (\Throwable) {
            // Tabel penugasan struktural belum tersedia.
        }

        // 4. Tugas tambahan dari SK dan penugasan operasional.
        try {
            if (method_exists($user, 'tugasTambahan')) {
                foreach ($user->tugasTambahan as $task) {
                    if ($task?->nama_tugas) {
                        $raw[] = $task->nama_tugas;
                    }
                }
            }
        } catch (\Throwable) {
            // Tugas tambahan belum tersedia.
        }

        try {
            foreach (OtherTeacherTask::query()
                ->where('teacher_id', $user->id)
                ->where('is_active', 1)
                ->pluck('task_name') as $taskName) {
                if ($taskName) {
                    $raw[] = $taskName;
                }
            }
        } catch (\Throwable) {
            // Modul tugas operasional tidak aktif.
        }

        // 5. Penetapan kepala pada data induk. Kolom teks jabatan sering tidak
        //    ikut diperbarui saat seseorang ditetapkan sebagai kepala satuan
        //    pendidikan atau kepala asrama — mis. GTK yang merangkap Kepala
        //    Satuan Pendidikan sekaligus Kepala Asrama hanya tercatat
        //    "Kepala Asrama" di kepegawaian. Data induk (schools.principal_user_id,
        //    dormitories.head_id) adalah sumber kebenaran untuk peran ini.
        try {
            if (School::query()->where('principal_user_id', $user->id)->exists()) {
                $raw[] = 'Kepala Satuan Pendidikan';
            }
        } catch (\Throwable) {
            // Tabel satuan pendidikan belum siap.
        }

        try {
            if (Dormitory::query()->where('head_id', $user->id)->exists()) {
                $raw[] = 'Kepala Asrama';
            }
        } catch (\Throwable) {
            // Tabel asrama belum siap.
        }

        $normalized = array_values(array_unique(array_filter(array_map(
            static fn ($j) => strtolower(trim((string) $j)),
            $raw,
        ), static fn (string $j) => $j !== '')));

        return array_values(array_unique(array_merge($normalized, self::aliases($normalized))));
    }

    /**
     * Apakah user memiliki salah satu jabatan (perbandingan case-insensitive).
     *
     * @param  list<string>  $allowed
     */
    public static function hasAny(?User $user, array $allowed): bool
    {
        $normalized = array_map(
            static fn ($j) => strtolower(trim((string) $j)),
            $allowed,
        );

        return array_intersect($normalized, self::candidates($user)) !== [];
    }

    /**
     * Cek jabatan dengan pencocokan longgar: nama baku boleh muncul sebagai
     * awalan, mis. "kepala tata usaha sekolah" cocok dengan "kepala tata usaha".
     *
     * @param  list<string>  $allowed
     */
    public static function hasAnyLoose(?User $user, array $allowed): bool
    {
        $candidates = self::candidates($user);

        foreach ($allowed as $needle) {
            $needle = strtolower(trim((string) $needle));

            if ($needle === '') {
                continue;
            }

            foreach ($candidates as $candidate) {
                if ($candidate === $needle || str_starts_with($candidate, $needle.' ')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Pimpinan satuan pendidikan (kepala/wakil). Dipakai gate yang harus
     * membedakan pimpinan sekolah dari akun asrama, terutama saat satu GTK
     * merangkap dua jabatan.
     */
    public static function isSchoolLeader(?User $user): bool
    {
        return self::hasAnyLoose($user, [
            'kepala satuan pendidikan',
            'kepala sekolah',
            'wakil kepala satuan pendidikan',
            'wakil kepala sekolah',
            'waka',
        ]);
    }

    /**
     * Alias baku: varian nama jabatan yang secara organisasi bermakna sama.
     *
     * @param  list<string>  $names
     * @return list<string>
     */
    private static function aliases(array $names): array
    {
        $aliases = [];

        foreach ($names as $name) {
            // "Waka Kurikulum", "Waka Kesiswaan", "Waka Sarana & Prasarana"
            // semuanya adalah wakil kepala satuan pendidikan.
            if (preg_match('/^waka\b/', $name)) {
                $aliases[] = 'wakil kepala satuan pendidikan';
                $aliases[] = 'wakil kepala sekolah';
            }

            if (str_starts_with($name, 'wakil kepala satuan pendidikan')) {
                $aliases[] = 'wakil kepala sekolah';
                $aliases[] = 'waka';
            }

            if (str_starts_with($name, 'kepala satuan pendidikan')
                || str_starts_with($name, 'kepala sekolah')) {
                $aliases[] = 'kepala satuan pendidikan';
                $aliases[] = 'kepala sekolah';
            }

            if (str_starts_with($name, 'kepala staf tata usaha')
                || str_starts_with($name, 'kepala tu sekolah')
                || str_starts_with($name, 'kepala tata usaha sekolah')) {
                $aliases[] = 'kepala tata usaha';
            }

            if (str_starts_with($name, 'staf tu sekolah')
                || str_starts_with($name, 'staf tata usaha sekolah')) {
                $aliases[] = 'staf tata usaha';
            }

            if ($name === 'tu sekolah' || $name === 'tata usaha sekolah') {
                $aliases[] = 'tata usaha';
            }

            if (str_starts_with($name, 'guru ')) {
                $aliases[] = 'guru';
            }

            if (str_starts_with($name, 'wali kelas')) {
                $aliases[] = 'wali kelas';
            }

            if (str_starts_with($name, 'kepala asrama')
                || str_starts_with($name, 'wakil kepala asrama')) {
                $aliases[] = 'asrama';
            }
        }

        return $aliases;
    }
}
