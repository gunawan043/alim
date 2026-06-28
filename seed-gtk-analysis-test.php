<?php

/**
 * Test data seeder for GTK Analysis Engine.
 * Run via: php artisan tinker < seed-gtk-analysis-test.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo PHP_EOL.'=== CREATING TEST DATA FOR GTK ANALYSIS ENGINE ==='.PHP_EOL.PHP_EOL;

// 1. Work Unit
$workUnitId = DB::table('work_units')->where('code', 'TEST-WU')->value('id');
if (! $workUnitId) {
    $workUnitId = (string) Str::uuid();
    DB::table('work_units')->insert([
        'id' => $workUnitId,
        'name' => 'Pondok Test Unit',
        'code' => 'TEST-WU',
        'type' => 'Unit Akademik',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// 1. School
$schoolId = DB::table('schools')->where('name', 'Pondok Test')->value('id');
if (! $schoolId) {
    $schoolId = (string) Str::uuid();
    DB::table('schools')->insert([
        'id' => $schoolId,
        'name' => 'Pondok Test',
        'is_active' => 1,
        'kop_nama' => 'Pondok Test',
        'work_unit_id' => $workUnitId,
        'npsn' => '12345678',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
echo "WorkUnit: $workUnitId".PHP_EOL;
echo "School: $schoolId".PHP_EOL;

// 2. Academic Year
$ayId = DB::table('academic_years')->where('code', 'AY2026')->value('id');
if (! $ayId) {
    $ayId = (string) Str::uuid();
    DB::table('academic_years')->insert([
        'id' => $ayId,
        'name' => '2026/2027',
        'code' => 'AY2026',
        'is_active' => 1,
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
echo "AY: $ayId".PHP_EOL;

// 3. Subjects (3 subjects — Matematika has 2 teachers, Bahasa Indonesia has 1, IPA has 1)
$subjects = ['Matematika' => 'MTK', 'Bahasa Indonesia' => 'BIN', 'IPA' => 'IPA'];
$subjectIds = [];
foreach ($subjects as $name => $code) {
    $id = DB::table('subjects')->where('code', $code)->value('id');
    if (! $id) {
        $id = (string) Str::uuid();
        DB::table('subjects')->insert([
            'id' => $id, 'name' => $name, 'code' => $code,
            'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    $subjectIds[$name] = $id;
}
echo 'Subjects: '.count($subjectIds).PHP_EOL;

// 4. Grade Level
$glId = DB::table('grade_levels')->where('code', 'K7')->value('id');
if (! $glId) {
    $glId = (string) Str::uuid();
    DB::table('grade_levels')->insert([
        'id' => $glId, 'name' => 'Kelas 7', 'code' => 'K7',
        'level' => 7, 'is_active' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}
echo "Grade Level: $glId".PHP_EOL;

// 5. Study Group
$sgId = DB::table('study_groups')->where('code', '7A')->where('school_id', $schoolId)->value('id');
if (! $sgId) {
    $sgId = (string) Str::uuid();
    DB::table('study_groups')->insert([
        'id' => $sgId, 'school_id' => $schoolId, 'academic_year_id' => $ayId,
        'grade_level_id' => $glId, 'name' => '7A', 'code' => '7A',
        'capacity' => 30, 'is_active' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}
echo "Study Group: $sgId".PHP_EOL;

// 6. Decree
$decId = DB::table('institution_decrees')->where('decree_number', 'TEST/001/2026')->value('id');
if (! $decId) {
    $decId = (string) Str::uuid();
    DB::table('institution_decrees')->insert([
        'id' => $decId, 'school_id' => $schoolId, 'academic_year_id' => $ayId,
        'decree_number' => 'TEST/001/2026',
        'decree_type' => 'pembagian_tugas',
        'title' => 'SK Pembagian Tugas Mengajar',
        'description' => 'Test decree',
        'issued_date' => '2026-07-01',
        'effective_date' => '2026-07-01',
        'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
}
echo "Decree: $decId".PHP_EOL;

// 7. Create 3 GTK profiles (teachers)
$teachers = [
    ['name' => 'Budi Santoso', 'email' => 'budi@test.com', 'hours' => [['Matematika', 12], ['IPA', 8]]],   // total 20
    ['name' => 'Siti Aminah',  'email' => 'siti@test.com', 'hours' => [['Bahasa Indonesia', 10]]],          // total 10 (under)
    ['name' => 'Joko Susanto', 'email' => 'joko@test.com', 'hours' => [['Matematika', 14]]],                // total 14
];
$gtkIds = [];
foreach ($teachers as $t) {
    $uId = DB::table('users')->where('email', $t['email'])->value('id');
    if (! $uId) {
        $uId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $uId, 'name' => $t['name'], 'email' => $t['email'],
            'password' => bcrypt('password'), 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    $gId = DB::table('gtk_profiles')->where('user_id', $uId)->value('id');
    if (! $gId) {
        $gId = (string) Str::uuid();
        DB::table('gtk_profiles')->insert([
            'id' => $gId, 'user_id' => $uId,
            'nik' => encrypt('1234567890123456'),
            'jenis_kelamin' => 'L',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    $gtkIds[$t['email']] = $gId;
}
echo 'GTK profiles: '.count($gtkIds).PHP_EOL;

// 8. Teaching assignments — delete existing for this test decree first to avoid duplicates
DB::table('teaching_assignments')->where('decree_id', $decId)->delete();
foreach ($teachers as $t) {
    $teacherGtkId = $gtkIds[$t['email']];
    foreach ($t['hours'] as [$subjectName, $hours]) {
        DB::table('teaching_assignments')->insert([
            'id' => (string) Str::uuid(),
            'decree_id' => $decId,
            'teacher_id' => $teacherGtkId,
            'school_id' => $schoolId,
            'academic_year_id' => $ayId,
            'study_group_id' => $sgId,
            'subject_id' => $subjectIds[$subjectName],
            'role' => 'guru_mapel',
            'weekly_hours' => $hours,
            'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
$taCount = DB::table('teaching_assignments')->where('decree_id', $decId)->count();
echo "Teaching assignments: $taCount".PHP_EOL;

// Save context
file_put_contents('/tmp/test-ids.json', json_encode([
    'school' => $schoolId,
    'ay' => $ayId,
    'subjects' => $subjectIds,
    'sg' => $sgId,
    'gtk' => $gtkIds,
    'dec' => $decId,
    'gl' => $glId,
], JSON_PRETTY_PRINT));

echo PHP_EOL.'=== TEST DATA CREATED ==='.PHP_EOL;
echo 'Schools: '.DB::table('schools')->count().PHP_EOL;
echo 'AY:      '.DB::table('academic_years')->count().PHP_EOL;
echo 'GTK:     '.DB::table('gtk_profiles')->count().PHP_EOL;
echo 'TA:      '.DB::table('teaching_assignments')->count().PHP_EOL;
