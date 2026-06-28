<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CascadeSmokeSeeder extends Seeder
{
    public function run(): void
    {
        $wu = DB::table('work_units')->where('id', '60000000-6000-6000-7000-700000000000')->first();
        if (! $wu) {
            DB::table('work_units')->insert([
                'id' => '60000000-6000-6000-7000-700000000000',
                'name' => 'Yayasan Smoke Test',
                'code' => 'YST',
                'type' => 'Unsur Pimpinan',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $school = DB::table('schools')->where('id', '70000000-7000-7000-7000-700000000000')->first();
        if (! $school) {
            DB::table('schools')->insert([
                'id' => '70000000-7000-7000-7000-700000000000',
                'work_unit_id' => '60000000-6000-6000-7000-700000000000',
                'npsn' => 'SMOKE001',
                'name' => 'SMOKE School',
                'school_code' => 'SMK',
                'school_level' => 'sma',
                'school_status' => 'negeri',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $year = DB::table('academic_years')->where('id', '80000000-8000-8000-7000-700000000000')->first();
        if (! $year) {
            DB::table('academic_years')->insert([
                'id' => '80000000-8000-8000-7000-700000000000',
                'name' => 'TA 2026/2027',
                'semester' => 'ganjil',
                'is_active' => 1,
                'start_date' => '2026-07-15',
                'end_date' => '2027-06-30',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $grade = DB::table('grade_levels')->where('id', '90000000-9000-7000-7000-700000000000')->first();
        if (! $grade) {
            DB::table('grade_levels')->insert([
                'id' => '90000000-9000-7000-7000-700000000000',
                'school_id' => '70000000-7000-7000-7000-700000000000',
                'level' => 10,
                'name' => 'Kelas 10',
                'code' => 'X',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sg = DB::table('study_groups')->where('id', 'a0000000-a000-7000-7000-700000000000')->first();
        if (! $sg) {
            DB::table('study_groups')->insert([
                'id' => 'a0000000-a000-7000-7000-700000000000',
                'school_id' => '70000000-7000-7000-7000-700000000000',
                'academic_year_id' => '80000000-8000-8000-7000-700000000000',
                'grade_level_id' => '90000000-9000-7000-7000-700000000000',
                'name' => 'X-IPA-1',
                'code' => 'XIPA1',
                'capacity' => 32,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $subj = DB::table('subjects')->where('id', 'b0000000-b000-7000-7000-700000000000')->first();
        if (! $subj) {
            DB::table('subjects')->insert([
                'id' => 'b0000000-b000-7000-7000-700000000000',
                'school_id' => '70000000-7000-7000-7000-700000000000',
                'code' => 'MTK',
                'name' => 'Matematika',
                'category' => 'nasional',
                'credit_hours' => 4,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $studentId = 'c0000000-c000-7000-7000-700000000000';
        $existingUser = DB::table('users')->where('id', $studentId)->first();
        if (! $existingUser) {
            DB::table('users')->insert([
                'id' => $studentId,
                'name' => 'Smoke Student',
                'email' => 'smoke-student@example.test',
                'password' => \Hash::make('password'),
                'avatar' => 'default-avatar.jpg',
                'is_wali' => 0,
                'is_active' => 1,
                'failed_login_attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $existingStudent = DB::table('students')->where('id', $studentId)->first();
        if (! $existingStudent) {
            DB::table('students')->insert([
                'id' => $studentId,
                'user_id' => $studentId,
                'school_id' => '70000000-7000-7000-7000-700000000000',
                'nisn' => '99999001',
                'nis' => '88888001',
                'nik' => '3201010101010001',
                'name' => 'Smoke Student',
                'gender' => 'L',
                'special_needs' => 'tidak',
                'address' => 'Test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $existingHistory = DB::table('student_class_histories')
            ->where('student_id', $studentId)
            ->where('study_group_id', 'a0000000-a000-7000-7000-700000000000')
            ->where('academic_year_id', '80000000-8000-8000-7000-700000000000')
            ->first();
        if (! $existingHistory) {
            DB::table('student_class_histories')->insert([
                'id' => 'd0000000-d000-7000-7000-700000000000',
                'student_id' => $studentId,
                'study_group_id' => 'a0000000-a000-7000-7000-700000000000',
                'academic_year_id' => '80000000-8000-8000-7000-700000000000',
                'is_active' => 1,
                'attendance_number' => 1,
                'notes' => 'Smoke cascade',
                'join_date' => '2026-07-15',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Smoke seed ready');
    }
}
