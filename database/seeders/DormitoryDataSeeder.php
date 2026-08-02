<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryAttendance;
use App\Models\DormitoryAttendanceRecap;
use App\Models\DormitoryInventory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryRoomMove;
use App\Models\DormitoryViolation;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentMahrom;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DormitoryDataSeeder
 *
 * Membuat data dummy lengkap untuk semua menu Asrama:
 * 1. Students (santri putra & putri) untuk SMP/SMA + mahrom
 * 2. Study groups + class histories
 * 3. Dormitory residents (penghuni) + bed numbers
 * 4. Dormitory attendances (absensi harian) — Mei 2026
 * 5. Dormitory attendance recaps (rekap bulanan)
 * 6. Dormitory permits (izin Pulang/Keluar/Berobat)
 * 7. Dormitory violations (pelanggaran)
 * 8. Dormitory room moves (mutasi kamar)
 * 9. Dormitory inventories (inventaris kamar)
 *
 * Run AFTER: SchoolSeeder, GradeLevelSeeder, StudyGroupSeeder, DormitorySeeder
 */
class DormitoryDataSeeder extends Seeder
{
    // ── Helpers ──────────────────────────────────────────────────────────────

    private function schoolByName(string $pattern): ?School
    {
        return School::where('name', 'like', "%{$pattern}%")->first();
    }

    private function academicYear(string $name, ?string $semester = null): ?AcademicYear
    {
        $q = AcademicYear::where('name', $name);
        if ($semester) {
            $q->where('semester', $semester);
        }

        return $q->first();
    }

    private function studyGroupFor(string $schoolPattern, string $kelas): ?StudyGroup
    {
        $school = $this->schoolByName($schoolPattern);
        if (! $school) {
            return null;
        }
        $activeYear = $this->academicYear('2025/2026', 'ganjil');
        if (! $activeYear) {
            return null;
        }

        return StudyGroup::where('school_id', $school->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('name', $kelas)
            ->first();
    }

    private function getOrCreateStudyGroup(School $school, GradeLevel $gradeLevel, string $name, string $letter): StudyGroup
    {
        $activeYear = $this->academicYear('2025/2026', 'ganjil');
        $sg = StudyGroup::where('school_id', $school->id)
            ->where('academic_year_id', $activeYear?->id)
            ->where('name', $name)
            ->first();
        if ($sg) {
            return $sg;
        }

        return StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $activeYear?->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => $name,
            'code' => $name,
            'capacity' => 32,
            'room' => "Ruang {$gradeLevel->level}{$letter}",
            'curriculum_type' => 'merdeka',
            'shift' => 'pagi',
            'is_active' => true,
        ]);
    }

    private function getOrCreateGradeLevel(School $school, int $level): GradeLevel
    {
        $gl = GradeLevel::where('school_id', $school->id)->where('level', $level)->first();
        if ($gl) {
            return $gl;
        }

        $roman = match ($level) {
            7 => 'VII', 8 => 'VIII', 9 => 'IX',
            10 => 'X', 11 => 'XI', 12 => 'XII',
            default => (string) $level,
        };

        return GradeLevel::create([
            'school_id' => $school->id,
            'level' => $level,
            'name' => "Kelas {$level}",
            'code' => $roman,
            'is_active' => true,
        ]);
    }

    // ── Student factory (idempotent — skip if nisn already exists) ───────

    private function createStudent(array $data): Student
    {
        $nisn = $data['nisn'] ?? null;

        $defaults = [
            'religion' => 'Islam',
            'entry_date' => '2025-07-14',
            'status' => 'active',
            'address' => $data['address'] ?? ('Jl. Pondok No. '.rand(1, 200).', Mataram, NTB'),
            'father_occupation' => 'Wiraswasta',
            'mother_occupation' => 'Ibu Rumah Tangga',
            'father_education' => 'SMA/Sederajat',
            'mother_education' => 'SMA/Sederajat',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // If nisn provided, try to find existing student first
        if ($nisn) {
            $existing = Student::where('nisn', $nisn)->first();
            if ($existing) {
                // Merge updated fields but keep the existing record
                $existing->update(array_merge($defaults, $data));

                return $existing;
            }

            // No existing — create normally (will be inserted)
            return Student::create(array_merge(['id' => (string) Str::uuid()], $defaults, $data));
        }

        // No nisn — just create
        return Student::create(array_merge(['id' => (string) Str::uuid()], $defaults, $data));
    }

    private function createMahrom(Student $student, array $ayah, array $ibu): void
    {
        // Ayah (skip if already exists)
        if (! StudentMahrom::where('student_id', $student->id)->where('relationship', 'ayah')->exists()) {
            StudentMahrom::create([
                'id' => (string) Str::uuid(),
                'student_id' => $student->id,
                'name' => $ayah['name'],
                'id_number' => '52'.rand(1000, 9999).rand(1000, 9999).rand(100, 999),
                'relationship' => 'ayah',
                'phone' => '08'.rand(1000, 9999).rand(1000, 9999),
                'address' => $student->address,
                'is_active' => true,
                'is_primary' => true,
            ]);
        }

        // Ibu (skip if already exists)
        if (! StudentMahrom::where('student_id', $student->id)->where('relationship', 'ibu')->exists()) {
            StudentMahrom::create([
                'id' => (string) Str::uuid(),
                'student_id' => $student->id,
                'name' => $ibu['name'],
                'id_number' => '52'.rand(1000, 9999).rand(1000, 9999).rand(100, 999),
                'relationship' => 'ibu',
                'phone' => '08'.rand(1000, 9999).rand(1000, 9999),
                'address' => $student->address,
                'is_active' => true,
                'is_primary' => false,
            ]);
        }
    }

    private function classHistory(Student $student, StudyGroup $sg): void
    {
        $activeYear = $this->academicYear('2025/2026', 'ganjil');
        if (! $activeYear) {
            return;
        }

        // Skip if already exists for this year
        if (StudentClassHistory::where('student_id', $student->id)
            ->where('academic_year_id', $activeYear->id)
            ->exists()) {
            return;
        }

        StudentClassHistory::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'study_group_id' => $sg->id,
            'academic_year_id' => $activeYear->id,
            'is_active' => true,
            'join_date' => '2025-07-14',
            'attendance_number' => rand(1, 32),
        ]);
    }

    // ── Attendence helpers ──────────────────────────────────────────────────

    private function sessions(): array
    {
        return ['subuh', 'pagi', 'siang', 'sore', 'isya', 'malam'];
    }

    private function randomStatus(): string
    {
        $w = ['hadir' => 75, 'izin' => 8, 'sakit' => 5, 'alpa' => 3, 'pulang' => 9];
        $total = array_sum($w);
        $rand = rand(1, $total);
        $cumsum = 0;
        foreach ($w as $status => $weight) {
            $cumsum += $weight;
            if ($rand <= $cumsum) {
                return $status;
            }
        }

        return 'hadir';
    }

    private function recapMonth(Student $student, DormitoryRoom $room, Dormitory $dorm,
        AcademicYear $year, int $month, int $yearNum): void
    {
        $semester = ($month >= 7 && $month <= 12) ? 'ganjil' : 'genap';
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $yearNum);
        $schoolDays = max(1, $days - 8); // minus Sundays

        $hadir = (int) floor($schoolDays * 0.88);
        $izin = rand(1, 3);
        $sakit = rand(0, 2);
        $alpa = rand(0, 1);
        $pulang = rand(0, 2);

        DormitoryAttendanceRecap::firstOrCreate(
            [
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'recap_month' => $month,
                'recap_year' => $yearNum,
            ],
            [
                'room_id' => $room->id,
                'dormitory_id' => $dorm->id,
                'semester' => $semester,
                'total_hadir' => $hadir,
                'total_izin' => $izin,
                'total_sakit' => $sakit,
                'total_alpa' => $alpa,
                'total_pulang' => $pulang,
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // MAIN RUN
    // ─────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        // ── Resolve dependencies ────────────────────────────────────────────
        $schoolSmpPutra = $this->schoolByName('SMP IT Putra Abu Hurairah Mataram');
        $schoolSmaPutra = $this->schoolByName('SMA IT Putra Abu Hurairah Mataram');
        $schoolSmpPutri = $this->schoolByName('SMP IT Putri Abu Hurairah Mataram');
        $schoolSmaPutri = $this->schoolByName('SMA IT Putri Abu Hurairah Mataram');
        $schoolMaPutri = $this->schoolByName('MA Plus Abu Hurairah Mataram');

        $activeYear = $this->academicYear('2025/2026', 'ganjil');
        $genapYear = $this->academicYear('2025/2026', 'genap');
        $dormPutra = Dormitory::where('code', 'ASR-001')->first();
        $dormPutri = Dormitory::where('code', 'ASR-002')->first();

        $kepalaAsrama = User::where('email', 'kepala.asrama@example.com')->first();
        $adminAsrama = User::where('email', 'admin.asrama@example.com')->first();
        $gtkUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'Guru'))->get();

        $activeYearId = $activeYear?->id;

        if (! $dormPutra || ! $activeYear) {
            $this->command->error('❌ Dormitory or AcademicYear tidak ditemukan.');

            return;
        }

        // ── Collect rooms by dormitory ─────────────────────────────────────
        $putraRooms = DormitoryRoom::where('dormitory_id', $dormPutra->id)->orderBy('code')->get();
        $putriRooms = DormitoryRoom::where('dormitory_id', $dormPutri->id)->orderBy('code')->get();

        // ════════════════════════════════════════════════════════════════════
        // IDEMPOTENCY CHECK — if residents already exist, skip student/room creation
        // ════════════════════════════════════════════════════════════════════

        $existingResidents = DormitoryResident::with(['student', 'room'])
            ->where('academic_year_id', $activeYearId)
            ->where('is_active', 1)
            ->get();

        $createdStudents = [];
        if ($existingResidents->isNotEmpty()) {
            // Load from existing data (idempotent — skip all creation)
            $this->command->warn('⚠️  Penghuni asrama sudah ada. Memuat data yang sudah ada...');
            foreach ($existingResidents as $res) {
                $createdStudents[$res->student_id] = [
                    'student' => $res->student,
                    'gender' => $res->student->gender,
                    'room' => $res->room,
                    'bed' => $res->bed_number,
                    'dorm' => Dormitory::find($res->dormitory_id),
                    'school_id' => $res->student->school_id,
                    'resident' => $res,
                ];
            }
            $this->command->info("  ✅ Dimuat {$existingResidents->count()} penghuni yang sudah ada.");
        } else {
            // ── Fresh creation ───────────────────────────────────────────────
            $this->command->info('=== DormitoryDataSeeder ===');
            $this->command->info("  ActiveYear : {$activeYear?->name} ({$activeYearId})");
            $this->command->info("  AsramaPutra: {$dormPutra?->name} ({$dormPutra?->id})");
            $this->command->info("  AsramaPutri: {$dormPutri?->name} ({$dormPutri?->id})");

            if (! $dormPutra || ! $activeYear) {
                $this->command->error('❌ Dormitory or AcademicYear tidak ditemukan.');

                return;
            }

            // ════════════════════════════════════════════════════════════════════
            // STEP 1 — CREATE SANTRI PUTRA (SMP + SMA)
            // ════════════════════════════════════════════════════════════════════

            $this->command->info("\n[1/8] Membuat Santri Putra...");

            // Names pool for male students
            $maleFirstNames = [
                'Muhammad', 'Ahmad', 'Faris', 'Zidan', 'Rizqi', 'Hafiz',
                'Athar', 'Abdul', 'Khalish', 'Maula', 'Zaqy', 'Fathir',
                'Arkan', 'Faiq', 'Haidar', 'Ibnu', 'Jundullah', 'Khalid',
                'Luqman', 'Mikail', 'Nabil', 'Osama', 'Putra', 'Qais',
                'Raffi', 'Syauqi', 'Tariq', 'Umar', 'Yusuf', 'Zahran',
                'Al-Fattah', 'Badr', 'Daffa', 'Eksa', 'Fajri', 'Ghani',
                'Hasan', 'Ibrahim', 'Johan', 'Kamal', 'Labib', 'Mahdi',
                'Naufal', 'Octavian', 'Rafli', 'Satria', 'Tamam',
            ];

            $maleSecondNames = [
                'Pratama', 'Al-Fatih', 'Nugroho', 'Rahman', 'Firdaus',
                'Azhari', 'Hanif', 'Saputra', 'Putra', 'Ramadhan',
                'Al-Barra', 'Zhafran', 'Akbar', 'Fauzi', 'Majid',
                'Mahdi', 'Wibowo', 'Santoso', 'Kusuma', 'Hakim',
                'Hidayah', 'Nur', 'Abidin', 'Munir', 'Jamil',
                'Maula', 'Salman', 'Falah', 'Rizqullah', 'Al-Qasim',
                'Amanullah', 'Bashir', 'Dhia', 'Fachri', 'Ghazy',
                'Habib', 'Ichsan', 'Jundi', 'Kautsar', 'Luthfi',
                'Musyafa', 'Nashir', 'Qusyairi', 'Rafie', 'Syamil',
            ];

            // Santri count: fill all putra rooms (20 rooms × 4 beds = 80)
            $santriTarget = min($putraRooms->count() * 4, 80);
            $santriPerLevel = [7 => 27, 8 => 27, 9 => 26]; // SMP VII, VIII, IX

            $createdStudents = [];
            $idx = 0;

            foreach ($santriPerLevel as $gradeLevel => $count) {
                $gradeName = "Kelas {$gradeLevel}";
                $romanNum = match ($gradeLevel) {
                    7 => 'VII', 8 => 'VIII', 9 => 'IX', default => (string) $gradeLevel
                };

                // Ensure grade level exists
                $gl = null;
                if ($schoolSmpPutra) {
                    $gl = $this->getOrCreateGradeLevel($schoolSmpPutra, $gradeLevel);
                }

                // Ensure study groups exist (VII-A, VII-B, VII-C for SMP)
                $sgLetters = ['A', 'B', 'C'];
                $perSg = (int) ceil($count / count($sgLetters));
                $sgs = [];
                foreach ($sgLetters as $letter) {
                    $name = "{$romanNum}-{$letter}";
                    if ($schoolSmpPutra && $gl) {
                        $sg = $this->getOrCreateStudyGroup($schoolSmpPutra, $gl, $name, $letter);
                        $sgs[] = $sg;
                    }
                }

                for ($i = 0; $i < $count; $i++) {
                    $firstName = $maleFirstNames[($idx) % count($maleFirstNames)];
                    $secondName = $maleSecondNames[($idx + rand(0, 5)) % count($maleSecondNames)];
                    $fullName = "{$firstName} {$secondName}";

                    $sg = $sgs[$i % count($sgs)] ?? null;
                    $birthYear = 2010 + (9 - $gradeLevel);
                    $birthMonth = rand(1, 12);
                    $birthDay = rand(1, 28);

                    $student = $this->createStudent([
                        'name' => $fullName,
                        'gender' => 'L',
                        'nisn' => (string) (10000000 + $idx + 100),
                        'nis' => (string) (20000 + $idx + 100),
                        'school_id' => $schoolSmpPutra?->id,
                        'birth_place' => 'Mataram',
                        'birth_date' => "{$birthYear}-".str_pad($birthMonth, 2, '0', STR_PAD_LEFT).'-'.str_pad($birthDay, 2, '0', STR_PAD_LEFT),
                        'father_name' => "Bapak {$secondName} bin Ayah {$idx}",
                        'mother_name' => "Ibu {$secondName} binti Ibu {$idx}",
                        'entry_grade_level' => $gradeLevel,
                    ]);

                    // Mahrom
                    $this->createMahrom($student, [
                        'name' => "Bapak {$secondName} bin Ayah {$idx}",
                    ], [
                        'name' => "Ibu {$secondName} binti Ibu {$idx}",
                    ]);

                    // Class history
                    if ($sg) {
                        $this->classHistory($student, $sg);
                    }

                    $createdStudents[$student->id] = [
                        'student' => $student,
                        'gender' => 'L',
                        'room' => null,
                        'bed' => null,
                        'dorm' => $dormPutra,
                        'school_id' => $schoolSmpPutra?->id,
                    ];

                    $idx++;
                    if ($idx >= $santriTarget) {
                        break 2;
                    }
                }
            }

            // SMA Putra (Kelas X, XI, XII) — remaining slots
            if ($idx < $santriTarget && $schoolSmaPutra) {
                $smaGrades = [10 => 15, 11 => 15, 12 => 10]; // remaining slots per level
                $romanSma = [10 => 'X', 11 => 'XI', 12 => 'XII'];
                $smaLetters = ['A', 'B'];

                foreach ($smaGrades as $gradeLevel => $count) {
                    if ($idx >= $santriTarget) {
                        break;
                    }
                    $count = min($count, $santriTarget - $idx);

                    $romanNum = $romanSma[$gradeLevel];
                    $gl = $this->getOrCreateGradeLevel($schoolSmaPutra, $gradeLevel);

                    $sgs = [];
                    foreach ($smaLetters as $letter) {
                        $name = "{$romanNum}-{$letter}";
                        $sg = $this->getOrCreateStudyGroup($schoolSmaPutra, $gl, $name, $letter);
                        $sgs[] = $sg;
                    }

                    for ($i = 0; $i < $count; $i++) {
                        $firstName = $maleFirstNames[($idx) % count($maleFirstNames)];
                        $secondName = $maleSecondNames[($idx + rand(0, 5)) % count($maleSecondNames)];
                        $fullName = "{$firstName} {$secondName}";
                        $sg = $sgs[$i % count($sgs)] ?? null;
                        $birthYear = 2010 + (10 - $gradeLevel);
                        $birthMonth = rand(1, 12);
                        $birthDay = rand(1, 28);

                        $student = $this->createStudent([
                            'name' => $fullName,
                            'gender' => 'L',
                            'nisn' => (string) (10000000 + $idx + 200),
                            'nis' => (string) (30000 + $idx + 200),
                            'school_id' => $schoolSmaPutra?->id,
                            'birth_place' => 'Mataram',
                            'birth_date' => "{$birthYear}-".str_pad($birthMonth, 2, '0', STR_PAD_LEFT).'-'.str_pad($birthDay, 2, '0', STR_PAD_LEFT),
                            'father_name' => "Bapak {$secondName} bin Ayah {$idx}",
                            'mother_name' => "Ibu {$secondName} binti Ibu {$idx}",
                            'entry_grade_level' => $gradeLevel,
                        ]);

                        $this->createMahrom($student, [
                            'name' => "Bapak {$secondName} bin Ayah {$idx}",
                        ], [
                            'name' => "Ibu {$secondName} binti Ibu {$idx}",
                        ]);

                        if ($sg) {
                            $this->classHistory($student, $sg);
                        }

                        $createdStudents[$student->id] = [
                            'student' => $student,
                            'gender' => 'L',
                            'room' => null,
                            'bed' => null,
                            'dorm' => $dormPutra,
                            'school_id' => $schoolSmaPutra?->id,
                        ];
                        $idx++;
                    }
                }
            }

            $this->command->info("  ✅ {$idx} santri putra dibuat");

            // ════════════════════════════════════════════════════════════════════
            // STEP 2 — CREATE SANTRI PUTRI (for Asrama Putri)
            // ════════════════════════════════════════════════════════════════════

            $this->command->info("\n[2/8] Membuat Santri Putri...");

            $femaleFirstNames = [
                'Aisyah', 'Putri', 'Nayla', 'Salsabila', 'Anindya', 'Devina',
                'Qonitat', 'Shafira', 'Aurelia', 'Nabilah', 'Khadijah', 'Asyafa',
                'Nurul', 'Zahra', 'Hafizah', 'Almahyra', 'Balqis', 'Citra',
                'Dinda', 'Erlina', 'Farah', 'Ghina', 'Hana', 'Ika',
                'Jasmine', 'Kayla', 'Lina', 'Maya', 'Nisa', 'Olivia',
                'Putri', 'Rani', 'Sari', 'Tika', 'Ulfah', 'Vina',
            ];

            $femaleSecondNames = [
                'Nur Zahra', 'Maheswari', 'Lestari', 'Khairunnisa', 'Ayuni',
                'Hidayah', 'Aini', 'Putriani', 'Khoirunnisa', 'Hikmah',
                'Amani', 'Azhari', 'Nurhaliza', 'Rahma', 'Salsabila',
                'Andini', 'Aprilianti', 'Fadhillah', 'Gustiana', 'Harapan',
                'Indah', 'Julaeha', 'Komariah', 'Lailatul', 'Maesaroh',
            ];

            $putriTarget = $putriRooms->count() * 4; // Fill all putri rooms
            $idxPutri = 0;
            $putriSchool = $schoolSmpPutri ?? $schoolMaPutri ?? null;

            // Santri Putri: mix of SMP Putri, SMA Putri, MA Plus
            $putriSchools = array_filter([$schoolSmpPutri, $schoolSmaPutri, $schoolMaPutri]);

            foreach ($putriSchools as $pSchool) {
                if ($idxPutri >= $putriTarget) {
                    break;
                }

                $schoolLevel = $pSchool->school_level ?? 'smp';
                $gradeRanges = match ($schoolLevel) {
                    'smp' => [7 => 20, 8 => 20, 9 => 20],
                    'sma' => [10 => 12, 11 => 12, 12 => 8],
                    default => [7 => 15, 8 => 15, 9 => 10],
                };

                foreach ($gradeRanges as $gradeLevel => $count) {
                    if ($idxPutri >= $putriTarget) {
                        break;
                    }

                    $romanNum = match ($gradeLevel) {
                        7 => 'VII', 8 => 'VIII', 9 => 'IX',
                        10 => 'X', 11 => 'XI', 12 => 'XII',
                        default => (string) $gradeLevel,
                    };

                    $gl = $this->getOrCreateGradeLevel($pSchool, $gradeLevel);
                    $letters = $schoolLevel === 'smp' ? ['A', 'B', 'C'] : ['A', 'B'];
                    $sgs = [];
                    foreach ($letters as $letter) {
                        $sg = $this->getOrCreateStudyGroup($pSchool, $gl, "{$romanNum}-{$letter}", $letter);
                        $sgs[] = $sg;
                    }

                    $remaining = min($count, $putriTarget - $idxPutri);
                    for ($i = 0; $i < $remaining; $i++) {
                        $firstName = $femaleFirstNames[($idxPutri) % count($femaleFirstNames)];
                        $secondName = $femaleSecondNames[($idxPutri + rand(0, 5)) % count($femaleSecondNames)];
                        $fullName = "{$firstName} {$secondName}";
                        $sg = $sgs[$i % count($sgs)] ?? null;
                        $birthYear = 2010 + (9 - $gradeLevel);
                        $birthMonth = rand(1, 12);
                        $birthDay = rand(1, 28);

                        $student = $this->createStudent([
                            'name' => $fullName,
                            'gender' => 'P',
                            'nisn' => (string) (20000000 + $idxPutri + 100),
                            'nis' => (string) (40000 + $idxPutri + 100),
                            'school_id' => $pSchool?->id,
                            'birth_place' => 'Mataram',
                            'birth_date' => "{$birthYear}-".str_pad($birthMonth, 2, '0', STR_PAD_LEFT).'-'.str_pad($birthDay, 2, '0', STR_PAD_LEFT),
                            'father_name' => "Bapak {$secondName} bin Ayah {$idxPutri}",
                            'mother_name' => "Ibu {$secondName} binti Ibu {$idxPutri}",
                            'entry_grade_level' => $gradeLevel,
                        ]);

                        $this->createMahrom($student, [
                            'name' => "Bapak {$secondName} bin Ayah {$idxPutri}",
                        ], [
                            'name' => "Ibu {$secondName} binti Ibu {$idxPutri}",
                        ]);

                        if ($sg) {
                            $this->classHistory($student, $sg);
                        }

                        $createdStudents[$student->id] = [
                            'student' => $student,
                            'gender' => 'P',
                            'room' => null,
                            'bed' => null,
                            'dorm' => $dormPutri,
                            'school_id' => $pSchool?->id,
                        ];
                        $idxPutri++;
                    }
                }
            }

            $this->command->info("  ✅ {$idxPutri} santri putri dibuat");
            $totalSantri = $idx + $idxPutri;
            $this->command->info("  📊 Total semua santri: {$totalSantri}");

            // ════════════════════════════════════════════════════════════════════
            // STEP 3 — ASSIGN STUDENTS TO ROOMS (DORMITORY RESIDENTS)
            // ════════════════════════════════════════════════════════════════════

            $this->command->info("\n[3/8] Menempatkan santri ke kamar...");

            // Separate by gender
            $putraStudentIds = collect($createdStudents)
                ->filter(fn ($v) => $v['gender'] === 'L')
                ->pluck('student.id')
                ->toArray();

            $putriStudentIds = collect($createdStudents)
                ->filter(fn ($v) => $v['gender'] === 'P')
                ->pluck('student.id')
                ->toArray();

            // Assign to rooms
            $residentCount = 0;
            $residentMap = []; // student_id => resident_id

            // Putra residents
            foreach ($putraRooms as $room) {
                $bed = 1;
                while ($bed <= 4 && ! empty($putraStudentIds)) {
                    $studentId = array_shift($putraStudentIds);
                    if (! $studentId) {
                        break;
                    }

                    $existing = DormitoryResident::where('student_id', $studentId)
                        ->where('academic_year_id', $activeYearId)
                        ->where('is_active', 1)
                        ->exists();
                    if ($existing) {
                        continue;
                    }

                    $resident = DormitoryResident::create([
                        'id' => (string) Str::uuid(),
                        'student_id' => $studentId,
                        'room_id' => $room->id,
                        'dormitory_id' => $dormPutra->id,
                        'academic_year_id' => $activeYearId,
                        'bed_number' => $bed,
                        'is_active' => 1,
                        'check_in_date' => '2025-07-14',
                        'notes' => null,
                    ]);

                    $residentMap[$studentId] = $resident->id;
                    $createdStudents[$studentId]['room'] = $room;
                    $createdStudents[$studentId]['bed'] = $bed;
                    $residentCount++;
                    $bed++;
                }
            }

            // Putri residents
            foreach ($putriRooms as $room) {
                $bed = 1;
                while ($bed <= 4 && ! empty($putriStudentIds)) {
                    $studentId = array_shift($putriStudentIds);
                    if (! $studentId) {
                        break;
                    }

                    $existing = DormitoryResident::where('student_id', $studentId)
                        ->where('academic_year_id', $activeYearId)
                        ->where('is_active', 1)
                        ->exists();
                    if ($existing) {
                        continue;
                    }

                    $resident = DormitoryResident::create([
                        'id' => (string) Str::uuid(),
                        'student_id' => $studentId,
                        'room_id' => $room->id,
                        'dormitory_id' => $dormPutri->id,
                        'academic_year_id' => $activeYearId,
                        'bed_number' => $bed,
                        'is_active' => 1,
                        'check_in_date' => '2025-07-14',
                        'notes' => null,
                    ]);

                    $residentMap[$studentId] = $resident->id;
                    $createdStudents[$studentId]['room'] = $room;
                    $createdStudents[$studentId]['bed'] = $bed;
                    $residentCount++;
                    $bed++;
                }
            }

            $this->command->info("  ✅ {$residentCount} penghuni asrama dibuat");

            // ════════════════════════════════════════════════════════════════════
            // STEP 4 — DORMITORY ATTENDANCES (Mei 2026, all sessions)
            // ════════════════════════════════════════════════════════════════════

            $this->command->info("\n[4/8] Membuat absensi harian Mei 2026...");

            $maySessions = $this->sessions(); // subuh, pagi, siang, sore, isya, malam
            $mayDates = [];
            for ($d = 1; $d <= 5; $d++) {
                $mayDates[] = "2026-05-{$d}";
            }

            $attCount = 0;
            $waliKamar = $adminAsrama ?? $kepalaAsrama ?? $gtkUsers->first();

            foreach ($createdStudents as $data) {
                $student = $data['student'];
                $room = $data['room'];
                $dorm = $data['dorm'];
                if (! $room || ! $dorm) {
                    continue;
                }

                foreach ($mayDates as $date) {
                    foreach ($maySessions as $session) {
                        // Skip some sessions randomly to create variety
                        if (rand(1, 100) <= 5) {
                            continue;
                        } // 5% skip rate

                        $exists = DormitoryAttendance::where('student_id', $student->id)
                            ->where('room_id', $room->id)
                            ->where('attendance_date', $date)
                            ->where('session', $session)
                            ->exists();
                        if ($exists) {
                            continue;
                        }

                        $status = $this->randomStatus();

                        DormitoryAttendance::create([
                            'id' => (string) Str::uuid(),
                            'dormitory_id' => $dorm->id,
                            'room_id' => $room->id,
                            'student_id' => $student->id,
                            'academic_year_id' => $activeYearId,
                            'recorded_by' => $waliKamar?->id,
                            'attendance_date' => $date,
                            'session' => $session,
                            'status' => $status,
                            'notes' => null,
                            'verified_by' => ($status !== 'alpa') ? ($waliKamar?->id) : null,
                            'verified_at' => ($status !== 'alpa') ? now() : null,
                        ]);
                        $attCount++;
                    }
                }
            }

            $this->command->info("  ✅ {$attCount} record absensi dibuat");

            // Monthly recap
            $this->command->info('  Membuat rekap bulanan...');
            $recapCount = 0;
            $months = [
                ['month' => 7, 'year' => 2025],
                ['month' => 8, 'year' => 2025],
                ['month' => 9, 'year' => 2025],
                ['month' => 10, 'year' => 2025],
                ['month' => 11, 'year' => 2025],
                ['month' => 12, 'year' => 2025],
                ['month' => 1, 'year' => 2026],
                ['month' => 2, 'year' => 2026],
                ['month' => 3, 'year' => 2026],
                ['month' => 4, 'year' => 2026],
                ['month' => 5, 'year' => 2026],
            ];

            foreach ($createdStudents as $data) {
                $student = $data['student'];
                $room = $data['room'];
                $dorm = $data['dorm'];
                if (! $room || ! $dorm) {
                    continue;
                }

                foreach ($months as $m) {
                    $this->recapMonth($student, $room, $dorm, $activeYear, $m['month'], $m['year']);
                    $recapCount++;
                }
            }

            $this->command->info("  ✅ {$recapCount} rekap bulanan dibuat");

            // ════════════════════════════════════════════════════════════════════
            // STEP 5 — DORMITORY PERMITS (Izin Pulang/Keluar/Berobat)
            // ════════════════════════════════════════════════════════════════════

            $this->command->info("\n[5/8] Membuat data izin asrama...");

            $permitTypes = ['pulang', 'keluar_kota', 'berobat', 'keperluan_keluarga', 'lainnya'];
            $permitStatuses = ['pending', 'approved', 'approved', 'approved', 'returned', 'returned', 'overdue', 'rejected'];
            $destinations = [
                'Jl. Pendidikan No. 5, Mataram',
                'Jl. Lombok, Lombok Barat',
                'Jl. Pariwisata, Lombok Timur',
                'Jl. Utama, Lombok Utara',
                'Sumbawa Besar, Sumbawa',
                'Jl. Hasanuddin, Bima',
                'Jl. Garuda, Denpasar Bali',
            ];

            $permitCount = 0;
            $permits = [];

            $allStudentIds = collect($createdStudents)->pluck('student.id')->toArray();

            foreach ($allStudentIds as $studentId) {
                $data = $createdStudents[$studentId] ?? null;
                if (! $data || ! $data['room']) {
                    continue;
                }
                $dorm = $data['dorm'];

                // 2-3 permits per student
                $numPermits = rand(2, 3);
                for ($p = 0; $p < $numPermits; $p++) {
                    $permitType = $permitTypes[array_rand($permitTypes)];
                    $status = $permitStatuses[array_rand($permitStatuses)];
                    $depDate = now()->subDays(rand(5, 60));
                    $expReturn = (clone $depDate)->addDays(rand(1, 7));

                    $existing = DormitoryPermit::where('student_id', $studentId)
                        ->where('permit_type', $permitType)
                        ->whereDate('departure_datetime', $depDate->toDateTimeString())
                        ->exists();
                    if ($existing) {
                        continue;
                    }

                    $permit = DormitoryPermit::create([
                        'id' => (string) Str::uuid(),
                        'student_id' => $studentId,
                        'dormitory_id' => $dorm->id,
                        'room_id' => $data['room']->id,
                        'academic_year_id' => $activeYearId,
                        'permit_type' => $permitType,
                        'destination' => $destinations[array_rand($destinations)],
                        'purpose' => match ($permitType) {
                            'pulang' => 'Libur semester bersama keluarga',
                            'keluar_kota' => 'Keperluan keluarga di luar kota',
                            'berobat' => 'Kontrol kesehatan di RS Mataram',
                            'keperluan_keluarga' => 'Menemui keluarga',
                            'lainnya' => 'Keperluan mendesak',
                        },
                        'departure_datetime' => $depDate->format('Y-m-d H:i:s'),
                        'expected_return_datetime' => $expReturn->format('Y-m-d H:i:s'),
                        'actual_return_datetime' => in_array($status, ['returned', 'approved']) ? $expReturn->addHours(rand(1, 5))->format('Y-m-d H:i:s') : null,
                        'companion_name' => 'Orang Tua / Keluarga',
                        'companion_relation' => 'Orang Tua',
                        'companion_phone' => '08'.rand(1000, 9999).rand(1000, 9999),
                        'companion_is_mahrom' => true,
                        'mahrom_id' => null,
                        'status' => $status,
                        'secondary_status' => null,
                        'approved_by' => ($status === 'approved' || $status === 'returned') ? ($kepalaAsrama?->id) : null,
                        'approved_at' => ($status === 'approved' || $status === 'returned') ? now()->subDays(rand(1, 3)) : null,
                        'approval_note' => ($status === 'approved') ? 'Silakan pulang, jaga diri baik-baik.' : null,
                        'document_path' => null,
                        'created_by' => $adminAsrama?->id ?? $kepalaAsrama?->id,
                    ]);

                    $permits[] = $permit;
                    $permitCount++;
                }
            }

            $this->command->info("  ✅ {$permitCount} izin asrama dibuat");

            // ════════════════════════════════════════════════════════════════════
            // STEP 6 — DORMITORY VIOLATIONS
            // ════════════════════════════════════════════════════════════════════

            $this->command->info("\n[6/8] Membuat data pelanggaran asrama...");

            $violationTypes = [
                ['category' => 'ringan', 'type' => 'Terlambat shubuh', 'points' => 2, 'description' => 'Terlambat hadir dalam kegiatan shubuh lebih dari 10 menit'],
                ['category' => 'ringan', 'type' => 'Membersihkan kamar tidak rapi', 'points' => 1, 'description' => 'Kamar tidak bersih dan tidak rapi saatinspection'],
                ['category' => 'ringan', 'type' => 'Bermain HP setelah jam malam', 'points' => 2, 'description' => 'Ditemukan玩手机 setelah jam 21.30'],
                ['category' => 'ringan', 'type' => 'Membuang sampah sembarangan', 'points' => 1, 'description' => 'Membuang sampah tidak pada tempatnya'],
                ['category' => 'ringan', 'type' => 'Tidak mengikuti apel malam', 'points' => 2, 'description' => 'Tidak hadir tanpa izin pada apel malam'],
                ['category' => 'sedang', 'type' => 'Keluar asrama tanpa izin', 'points' => 5, 'description' => 'Keluar area asrama tanpa izin dari musyrif'],
                ['category' => 'sedang', 'type' => 'Membawa barang terlarang', 'points' => 5, 'description' => 'Ditemukan membawa物件 yang tidak diperbolehkan di asrama'],
                ['category' => 'sedang', 'type' => 'Berkata kasar kepada teman', 'points' => 4, 'description' => 'Menggunakan kata-kata kasar dan menyakiti perasaan teman'],
                ['category' => 'sedang', 'type' => 'Merusak fasilitas asrama', 'points' => 5, 'description' => 'Merusak meja belajar di kamar'],
                ['category' => 'sedang', 'type' => 'Tidak mengikuti kegiatan wajib', 'points' => 4, 'description' => 'Tidak hadir dalam kegiatan mengaji bersama tanpa alasan'],
                ['category' => 'berat', 'type' => 'Pulang tanpa izin', 'points' => 10, 'description' => 'Pulang ke rumah tanpa sepengetahuan musyrif/pengasuh'],
                ['category' => 'berat', 'type' => 'Membawa HP smartphone', 'points' => 8, 'description' => 'Ditemukan smartphone di kamar padahal dilarang'],
                ['category' => 'berat', 'type' => 'Merokok di area asrama', 'points' => 15, 'description' => 'Ditemukan bekas merokok di kamar mandi asrama'],
                ['category' => 'berat', 'type' => 'Ikut terlibat perkelahian', 'points' => 12, 'description' => 'Terlibat perkelahian dengan santri другой asrama'],
            ];

            $violationCount = 0;
            $violatedStudents = $allStudentIds;

            // Select ~40% of students to have violations
            $violationCandidates = array_slice($violatedStudents, 0, (int) floor(count($violatedStudents) * 0.4));

            foreach ($violationCandidates as $studentId) {
                $data = $createdStudents[$studentId] ?? null;
                if (! $data || ! $data['room']) {
                    continue;
                }
                $dorm = $data['dorm'];

                // 1-3 violations per student
                $numViolations = rand(1, 3);
                for ($v = 0; $v < $numViolations; $v++) {
                    $vtype = $violationTypes[array_rand($violationTypes)];
                    $violDate = now()->subDays(rand(1, 120));

                    DormitoryViolation::create([
                        'id' => (string) Str::uuid(),
                        'student_id' => $studentId,
                        'room_id' => $data['room']->id,
                        'dormitory_id' => $dorm->id,
                        'academic_year_id' => $activeYearId,
                        'recorded_by' => $adminAsrama?->id ?? $kepalaAsrama?->id,
                        'violation_date' => $violDate->toDateString(),
                        'violation_category' => $vtype['category'],
                        'violation_type' => $vtype['type'],
                        'description' => $vtype['description'],
                        'points' => $vtype['points'],
                        'action_taken' => match ($vtype['category']) {
                            'ringan' => 'Ditegur dan dibuat surat pernyataan',
                            'sedang' => 'Diberi sanksi tugas tambahan dan通知 orang tua',
                            'berat' => 'Diberi surat peringatan dan diskors sementara dari kegiatan asrama',
                        },
                        'follow_up' => 'Orang tua/wali telah дипаakkan через telepon',
                        'witness_id' => $gtkUsers->random()?->id ?? null,
                        'parent_notified_at' => now()->subDays(rand(0, 3)),
                    ]);
                    $violationCount++;
                }
            }

            $this->command->info("  ✅ {$violationCount} pelanggaran dibuat");

            // ════════════════════════════════════════════════════════════════════
            // STEP 7 — DORMITORY ROOM MOVES (Mutasi)
            // ════════════════════════════════════════════════════════════════════

            $this->command->info("\n[7/8] Membuat mutasi kamar...");

            $moveTypes = ['rotasi', 'permintaan', 'kondisi_kesehatan', 'lainnya'];
            $moveStatuses = ['approved', 'approved', 'pending'];

            $moveCount = 0;
            // Move 10% of residents
            $moveCandidates = array_slice($allStudentIds, 0, (int) floor(count($allStudentIds) * 0.1));

            foreach ($moveCandidates as $studentId) {
                $data = $createdStudents[$studentId] ?? null;
                if (! $data || ! $data['room']) {
                    continue;
                }
                $dorm = $data['dorm'];

                // Find another room in the same dorm
                $sameDormRooms = ($dorm->id === $dormPutra->id) ? $putraRooms : $putriRooms;
                $targetRooms = $sameDormRooms->where('id', '!=', $data['room']->id);
                if ($targetRooms->isEmpty()) {
                    continue;
                }

                $toRoom = $targetRooms->random();

                // Check if already has a move
                $existingMove = DormitoryRoomMove::where('student_id', $studentId)
                    ->where('academic_year_id', $activeYearId)
                    ->exists();
                if ($existingMove) {
                    continue;
                }

                $moveType = $moveTypes[array_rand($moveTypes)];
                $moveDate = now()->subDays(rand(15, 60));

                DormitoryRoomMove::create([
                    'id' => (string) Str::uuid(),
                    'student_id' => $studentId,
                    'from_room_id' => $data['room']->id,
                    'to_room_id' => $toRoom->id,
                    'dormitory_id' => $dorm->id,
                    'academic_year_id' => $activeYearId,
                    'move_date' => $moveDate->toDateString(),
                    'reason' => match ($moveType) {
                        'rotasi' => 'Rotasi kamar semester genap 2025/2026',
                        'permintaan' => 'Permintaan pindah karena kamar terlalu ramai',
                        'kondisi_kesehatan' => 'Memerlukan ruangan dengan ventilasi lebih baik',
                        'lainnya' => 'Penyesuaian jumlah penghuni kamar',
                    },
                    'move_type' => $moveType,
                    'approved_by' => $kepalaAsrama?->id,
                    'approval_status' => $moveStatuses[array_rand($moveStatuses)],
                    'notes' => 'Mutasi disetujui oleh kepala asrama',
                ]);

                // Update resident record
                DormitoryResident::where('student_id', $studentId)
                    ->where('academic_year_id', $activeYearId)
                    ->where('is_active', 1)
                    ->update([
                        'room_id' => $toRoom->id,
                        'bed_number' => rand(1, 4),
                    ]);

                $moveCount++;
            }

            $this->command->info("  ✅ {$moveCount} mutasi kamar dibuat");

            // ════════════════════════════════════════════════════════════════════
            // STEP 8 — DORMITORY INVENTORIES (Barang di Kamar)
            // ════════════════════════════════════════════════════════════════════

            $this->command->info("\n[8/8] Membuat inventaris kamar...");

            $itemTemplates = [
                ['name' => 'Meja Belajar', 'condition' => 'baik'],
                ['name' => 'Kursi Belajar', 'condition' => 'baik'],
                ['name' => 'Lemari Pakaian', 'condition' => 'baik'],
                ['name' => 'Kasur Busa', 'condition' => 'baik'],
                ['name' => 'Bantal', 'condition' => 'baik'],
                ['name' => 'Guling', 'condition' => 'baik'],
                ['name' => 'Sprei', 'condition' => 'baik'],
                ['name' => 'Lampu Tidur', 'condition' => 'perbaikan'],
                ['name' => 'Stop Kontak', 'condition' => 'baik'],
                ['name' => 'Kipas Angin', 'condition' => 'perbaikan'],
                ['name' => 'Al-Quran', 'condition' => 'baik'],
                ['name' => 'Mukena', 'condition' => 'baik'],
                ['name' => 'Sarung', 'condition' => 'baik'],
                ['name' => 'Tempat Wudhu', 'condition' => 'perbaikan'],
                ['name' => 'Cermin', 'condition' => 'baik'],
                ['name' => 'Gantungan Baju', 'condition' => 'baik'],
                ['name' => 'Tempat Sampah', 'condition' => 'rusak'],
                ['name' => 'Talang Air', 'condition' => 'perbaikan'],
                ['name' => 'Pengharum Ruangan', 'condition' => 'hilang'],
            ];

            $invCount = 0;
            $allRooms = $putraRooms->merge($putriRooms);

            foreach ($allRooms as $room) {
                $dorm = $room->dormitory_id === $dormPutra->id ? $dormPutra : $dormPutri;

                // 5-10 items per room
                $numItems = rand(5, 10);
                $usedNames = [];
                for ($i = 0; $i < $numItems; $i++) {
                    $template = $itemTemplates[array_rand($itemTemplates)];
                    $name = $template['name'];
                    if (in_array($name, $usedNames)) {
                        continue;
                    }
                    $usedNames[] = $name;

                    DormitoryInventory::create([
                        'id' => (string) Str::uuid(),
                        'room_id' => $room->id,
                        'dormitory_id' => $dorm->id,
                        'item_name' => $name,
                        'item_code' => 'INV-'.strtoupper(substr($name, 0, 3)).'-'.rand(100, 999),
                        'quantity' => rand(1, 4),
                        'condition' => $template['condition'],
                        'last_checked_at' => now()->subDays(rand(1, 30))->toDateString(),
                        'checked_by' => $adminAsrama?->id,
                        'notes' => null,
                    ]);
                    $invCount++;
                }
            }

            $this->command->info("  ✅ {$invCount} inventaris kamar dibuat");

            // ════════════════════════════════════════════════════════════════════
            // DONE (fresh creation mode)
            // ════════════════════════════════════════════════════════════════════

            $this->command->info('');
            $this->command->info('═══════════════════════════════════════════════════');
            $this->command->info('✅ DormitoryDataSeeder selesai!');
            $this->command->info('───────────────────────────────────────────────────');
            $this->command->info('  Santri Putra (L)  : '.collect($createdStudents)->filter(fn ($v) => $v['gender'] === 'L')->count());
            $this->command->info('  Santri Putri (P)  : '.collect($createdStudents)->filter(fn ($v) => $v['gender'] === 'P')->count());
            $this->command->info("  Penghuni Aktif    : {$residentCount}");
            $this->command->info("  Absensi Harian    : {$attCount}");
            $this->command->info("  Rekap Bulanan     : {$recapCount}");
            $this->command->info("  Izin Asrama       : {$permitCount}");
            $this->command->info("  Pelanggaran       : {$violationCount}");
            $this->command->info("  Mutasi Kamar      : {$moveCount}");
            $this->command->info("  Inventaris Kamar  : {$invCount}");
            $this->command->info('═══════════════════════════════════════════════════');
        } // ← end: if ($existingResidents->notEmpty()) else
    }
}
