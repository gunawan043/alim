<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\StudentClassHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = 'f6d943eb-6712-4050-9462-714da766cc2e'; // SD IT Putra Abu Hurairah Mataram
        $academicYearId = '3accbda9-3856-4f3e-a0cc-82cb7df8b3f1'; // 2025/2026

        // Map: grade_level_name => [study_group_uuid => kelas_name]
        $studyGroups = [
            'Kelas 1' => [
                '6f1d9a28-d4d0-49b6-8658-846a2e0c8716' => 'I-A',
                '072223cb-c8e1-4d75-b64b-02a30187cd3d' => 'I-B',
            ],
            'Kelas 2' => [
                'fa3e2a5e-ac6a-4d68-91d9-f39df405e8ac' => 'II-A',
                '38eeecce-2549-4491-a280-05bdb1f214fe' => 'II-B',
            ],
            'Kelas 3' => [
                '80de3619-d741-431b-9be4-b90aef14b47d' => 'III-A',
                '658ed75a-3f45-4063-bc99-3220c63d1dc8' => 'III-B',
            ],
            'Kelas 4' => [
                '7501a85b-5696-4938-8a06-54f0d6e162e6' => 'IV-A',
                '7b5e7c81-299a-4f23-b649-0fc7ab65c217' => 'IV-B',
            ],
            'Kelas 5' => [
                '4b48fd8a-db62-4190-838e-9da3fe26ccd5' => 'V-A',
                '8130018e-6d3f-4ee1-b5d0-59e62d270880' => 'V-B',
            ],
            'Kelas 6' => [
                '45c9cc35-8c3b-4300-8eef-896b56de2461' => 'VI-A',
                'dcfc5a81-84f6-4b9b-8810-9bf2429d9472' => 'VI-B',
            ],
        ];

        // NISN counter base
        $nisnBase = 10000001;
        $nisBase = 10001;
        $studentNum = 1;

        $students = [
            // ── KELAS 1 ──────────────────────────────────────────────
            ['name' => 'Muhammad Rafi Pratama', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2018-03-15', 'nisn' => null, 'nis' => null],
            ['name' => 'Aisyah Nur Zahra', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2018-07-22', 'nisn' => null, 'nis' => null],
            ['name' => 'Ahmad Fawwaz Al-Fatih', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2018-01-08', 'nisn' => null, 'nis' => null],
            ['name' => 'Putri Nurul Hidayah', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2018-05-30', 'nisn' => null, 'nis' => null],

            // ── KELAS 2 ──────────────────────────────────────────────
            ['name' => 'Muhammad Zidane Akbar', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2017-04-12', 'nisn' => null, 'nis' => null],
            ['name' => 'Salsabila Rahmanisa', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2017-09-18', 'nisn' => null, 'nis' => null],
            ['name' => 'Hafiz Dhimas Nugroho', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2017-02-25', 'nisn' => null, 'nis' => null],
            ['name' => 'Anindya Putri Maheswari', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2017-11-07', 'nisn' => null, 'nis' => null],

            // ── KELAS 3 ──────────────────────────────────────────────
            ['name' => 'Rizqi Ramadhani', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2016-06-14', 'nisn' => null, 'nis' => null],
            ['name' => 'Nayla Khairunnisa', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2016-08-03', 'nisn' => null, 'nis' => null],
            ['name' => 'Farhan Aqila Zhafran', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2016-12-20', 'nisn' => null, 'nis' => null],
            ['name' => 'Devina Ayu Lestari', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2016-03-29', 'nisn' => null, 'nis' => null],

            // ── KELAS 4 ──────────────────────────────────────────────
            ['name' => 'Athar Rizqullah', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2015-10-05', 'nisn' => null, 'nis' => null],
            ['name' => 'Qonitat Thoyyibah', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2015-01-16', 'nisn' => null, 'nis' => null],
            ['name' => 'Abdul Malik Al-Qasim', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2015-07-08', 'nisn' => null, 'nis' => null],
            ['name' => 'Shafira Nur Aini', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2015-04-21', 'nisn' => null, 'nis' => null],

            // ── KELAS 5 ──────────────────────────────────────────────
            ['name' => 'Khalish Salman Al-Farisi', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2014-09-11', 'nisn' => null, 'nis' => null],
            ['name' => 'Maula Haya Malik', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2014-02-28', 'nisn' => null, 'nis' => null],
            ['name' => 'Aurelia Zahra Putriani', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2014-06-17', 'nisn' => null, 'nis' => null],
            ['name' => 'Nabilah Syifa Khoirunnisa', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2014-11-02', 'nisn' => null, 'nis' => null],

            // ── KELAS 6 ──────────────────────────────────────────────
            ['name' => 'Muhammad Harits Dhiyaulhaq', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2013-08-14', 'nisn' => null, 'nis' => null],
            ['name' => 'Ahmad Zidan Al-Ghazali', 'gender' => 'L', 'birth_place' => 'Mataram', 'birth_date' => '2013-05-26', 'nisn' => null, 'nis' => null],
            ['name' => 'Khadijah Nurul Hikmah', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2013-01-09', 'nisn' => null, 'nis' => null],
            ['name' => 'Asyafa Zafira Amani', 'gender' => 'P', 'birth_place' => 'Mataram', 'birth_date' => '2013-10-30', 'nisn' => null, 'nis' => null],
        ];

        // Distribute: 2 students per rombel, cycling through the groups
        $groupMap = [];
        foreach ($studyGroups as $gradeName => $groups) {
            foreach ($groups as $sgId => $kelasName) {
                $groupMap[$sgId] = ['grade' => $gradeName, 'kelas' => $kelasName];
            }
        }
        $sgIds = array_keys($groupMap);

        foreach ($students as $index => &$student) {
            $sgId = $sgIds[$index % count($sgIds)];
            $nisnBase++;
            $nisBase++;

            $student['nisn'] = (string) $nisnBase;
            $student['nis'] = (string) $nisBase;
            $student['school_id'] = $schoolId;
            $student['religion'] = 'Islam';
            $student['entry_date'] = '2025-07-14';
            $student['entry_grade_level'] = (int) preg_replace('/[^0-9]/', '', $groupMap[$sgId]['grade']);
            $student['status'] = 'active';
            $student['address'] = 'Jl. Pendidikan No. ' . ($index + 1) . ', Mataram, NTB';
            $student['father_name'] = 'Bapak ' . explode(' ', $student['name'])[1] . ' bin Orang Tua';
            $student['mother_name'] = 'Ibu ' . explode(' ', $student['name'])[1] . ' binti Orang Tua';
            $student['father_occupation'] = 'Wiraswasta';
            $student['mother_occupation'] = 'Ibu Rumah Tangga';
            $student['father_education'] = 'SMA/Sederajat';
            $student['mother_education'] = 'SMA/Sederajat';
            $student['created_at'] = now();
            $student['updated_at'] = now();
            $student['id'] = (string) Str::uuid();

            // Create student
            Student::create($student);

            // Create class history entry
            StudentClassHistory::create([
                'id' => (string) Str::uuid(),
                'student_id' => $student['id'],
                'study_group_id' => $sgId,
                'academic_year_id' => $academicYearId,
                'is_active' => true,
                'join_date' => '2025-07-14',
                'attendance_number' => ($index % 2) + 1,
            ]);

            $studentNum++;
        }
    }
}
