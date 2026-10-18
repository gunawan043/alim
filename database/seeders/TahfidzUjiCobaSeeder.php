<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzGroupTeacher;
use App\Models\TahfidzPackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Data uji coba Tahfidz untuk akun Lalu Raditya Tri Pamungkas.
 *
 * Aman dijalankan berulang:
 * - sekolah pada data kepegawaian hanya diisi jika masih kosong;
 * - halaqah dicari berdasarkan guru + sekolah + tahun ajaran;
 * - santri yang sudah punya halaqah aktif tidak dipindahkan.
 */
class TahfidzUjiCobaSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()
            ->where('email', 'alfaroyaraditya@gmail.com')
            ->orWhere('name', 'like', '%LALU%TRI%PAMUNGKAS%')
            ->first();

        if (! $user) {
            throw new RuntimeException('Akun Lalu Raditya Tri Pamungkas tidak ditemukan.');
        }

        $school = School::query()
            ->where('name', 'like', '%SD IT Putra Abu Hurairah%')
            ->first()
            ?: School::query()
                ->withCount(['students as active_students_count' => fn ($query) => $query->where('status', 'active')])
                ->orderByDesc('active_students_count')
                ->first();

        if (! $school) {
            throw new RuntimeException('Satuan pendidikan tujuan tidak ditemukan.');
        }

        $academicYear = AcademicYear::query()->where('is_active', true)->first()
            ?: AcademicYear::query()->orderByDesc('start_date')->first();

        if (! $academicYear) {
            throw new RuntimeException('Tahun ajaran aktif belum tersedia.');
        }

        $students = Student::query()
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        if ($students->isEmpty()) {
            throw new RuntimeException('Tidak ada santri aktif di '.$school->name.'.');
        }

        DB::transaction(function () use ($user, $school, $academicYear, $students) {
            $employment = GtkEmployment::query()->where('user_id', $user->id)->first();

            if (! $employment) {
                $employment = GtkEmployment::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                    'status_kepegawaian' => 'PTY',
                    'jenis_gtk' => 'Pendidik / Guru',
                    'jabatan' => 'Guru Tahfidz',
                    'satuan_kerja' => $school->name,
                ]);
            } else {
                $updates = [];
                if (! $employment->school_id) {
                    $updates['school_id'] = $school->id;
                }
                if (! $employment->academic_year_id) {
                    $updates['academic_year_id'] = $academicYear->id;
                }
                if (! $employment->satuan_kerja) {
                    $updates['satuan_kerja'] = $school->name;
                }
                if ($updates) {
                    $employment->update($updates);
                }
            }

            $genders = $students->pluck('gender')->filter()->unique();
            $gender = $genders->count() === 1 && $genders->first() === 'L' ? 'putra' : 'putri';

            $group = TahfidzGroup::query()->firstOrCreate(
                [
                    'teacher_id' => $user->id,
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                    'name' => 'Halaqah Uji Coba — Lalu Tri Pamungkas',
                ],
                [
                    'work_unit_id' => $school->work_unit_id,
                    'tahfidz_package_id' => TahfidzPackage::query()->where('is_active', true)->value('id'),
                    'code' => 'UJI-LTP',
                    'gender' => $gender,
                    'coordinator_id' => $user->id,
                    'max_members' => max(60, $students->count() + 5),
                    'room' => 'Ruang Tahfidz',
                    'is_active' => true,
                    'notes' => 'Seeder uji coba Tahfidz untuk akun Lalu Raditya Tri Pamungkas.',
                ]
            );

            TahfidzGroupTeacher::updateOrCreate(
                [
                    'tahfidz_group_id' => $group->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'guru_utama',
                    'can_input_setoran' => true,
                    'is_active' => true,
                ]
            );

            foreach ($students as $student) {
                $existingMembership = TahfidzGroupMember::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('status', 'aktif')
                    ->first();

                if ($existingMembership) {
                    continue;
                }

                TahfidzGroupMember::create([
                    'tahfidz_group_id' => $group->id,
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'join_date' => now()->toDateString(),
                    'status' => 'aktif',
                ]);
            }

            $this->command?->info(sprintf(
                'Akun: %s | Sekolah: %s | Halaqah: %s | Santri aktif: %d',
                $user->name,
                $school->name,
                $group->name,
                $students->count()
            ));
        });
    }
}
