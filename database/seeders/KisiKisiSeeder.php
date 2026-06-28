<?php

namespace Database\Seeders;

use App\Models\BankSoal;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KisiKisiSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('[KisiKisiSeeder] Starting evaluation data seeder...');

        $school = $this->createSchool();
        $owner = $this->createOwner($school);
        [$mtk, $bin] = $this->createSubjects($school);
        $academicYear = $this->createAcademicYear($school);
        [$gradeVII, $gradeVIII] = $this->createGradeLevels($school);

        // Create TPs
        $tpsMatematika = $this->createMatematikaTPs($mtk, $school, $gradeVII, $gradeVIII, $academicYear, $owner);
        $tpsBindonesia = $this->createBindonesiaTPs($bin, $school, $gradeVII, $gradeVIII, $academicYear, $owner);

        // Create bank soal
        $bankMatematika = BankSoal::firstOrCreate(
            ['nama' => 'Bank Soal Matematika SMA'],
            [
                'school_id' => $school->id,
                'subject_id' => $mtk->id,
                'fase' => 'E',
                'jenis_soal' => 'campuran',
                'tingkat_kesulitan_target' => 'campuran',
                'is_public' => true,
                'owner_user_id' => $owner->id,
            ]
        );
        $bankBindonesia = BankSoal::firstOrCreate(
            ['nama' => 'Bank Soal B. Indonesia SMA'],
            [
                'school_id' => $school->id,
                'subject_id' => $bin->id,
                'fase' => 'E',
                'jenis_soal' => 'campuran',
                'tingkat_kesulitan_target' => 'campuran',
                'is_public' => true,
                'owner_user_id' => $owner->id,
            ]
        );

        $this->seedSoal($bankMatematika, $tpsMatematika, $owner);
        $this->seedSoal($bankBindonesia, $tpsBindonesia, $owner);

        // Seed Kisi-kisi
        $this->createKisiKisi($school, $mtk, $bin, $gradeVIII, $academicYear, $owner);

        $this->command->info('[KisiKisiSeeder] Done.');
    }

    private function createSchool(): object
    {
        $wu = \App\Models\WorkUnit::where('code', 'PAH-UAK-003')->first();
        if (! $wu) {
            $wu = \App\Models\WorkUnit::where('code', 'PAH-UAK-TEST')->first();
        }
        if (! $wu) {
            $pondok = \App\Models\WorkUnit::where('code', 'PAH-UPI-001')->first();
            $wu = \App\Models\WorkUnit::create([
                'code' => 'PAH-UAK-TEST',
                'name' => 'Testing SMP IT',
                'type' => 'Unit Akademik',
                'parent_id' => $pondok?->id,
                'is_active' => true,
            ]);
        }

        $school = \App\Models\School::firstOrCreate(
            ['work_unit_id' => $wu->id],
            [
                'name' => 'Testing SMP IT',
                'npsn' => '99999999',
                'nss' => '99999999',
                'school_level' => 'smp',
                'school_status' => 'swasta',
                'accreditation' => 'A',
                'accreditation_year' => 2026,
                'is_active' => true,
            ]
        );

        return $school;
    }

    private function createOwner(object $school): User
    {
        return User::firstOrCreate(
            ['email' => 'eval-seed@alim.local'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Eval Seeder Owner',
                'password' => bcrypt('eval-seed-2026'),
                'is_active' => true,
            ]
        );
    }

    private function createSubjects(object $school): array
    {
        $mat = Subject::firstOrCreate(
            ['school_id' => $school->id, 'code' => 'MTK'],
            ['name' => 'Matematika']
        );
        $bin = Subject::firstOrCreate(
            ['school_id' => $school->id, 'code' => 'BIN'],
            ['name' => 'Bahasa Indonesia']
        );

        return [$mat, $bin];
    }

    private function createGradeLevels(object $school): array
    {
        $vii = \App\Models\GradeLevel::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'VII'],
            ['level' => 7]
        );
        $viii = \App\Models\GradeLevel::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'VIII'],
            ['level' => 8]
        );

        return [$vii, $viii];
    }

    private function createAcademicYear(object $school): ?object
    {
        $ay = \App\Models\AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            ['semester' => 'ganjil', 'is_active' => true]
        );

        return $ay;
    }

    private function createMatematikaTPs(object $subject, object $school, $gVII, $gVIII, $ay, User $owner): array
    {
        $tuples = [
            [$gVII, 'TP.MTK.7.01', 'Membandingkan bilangan bulat dan pecahan', 'Pemahaman Konsep', 1],
            [$gVII, 'TP.MTK.7.02', 'Operasi penjumlahan dan pengurangan pecahan', 'Operasi', 2],
            [$gVIII, 'TP.MTK.8.01', 'Menyelesaikan persamaan linear satu variabel', 'Aljabar', 1],
            [$gVIII, 'TP.MTK.8.02', 'Menganalisis pola bilangan dan barisan aritmetika', 'Pola Bilangan', 2],
        ];
        $tps = [];
        foreach ($tuples as [$grade, $kode, $deskripsi, $elemen, $urutan]) {
            $tp = TujuanPembelajaran::firstOrCreate(
                ['kode_tp' => $kode],
                [
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'grade_level_id' => $grade->id,
                    'academic_year_id' => $ay?->id,
                    'semester' => 'ganjil',
                    'fase' => 'E',
                    'deskripsi' => $deskripsi,
                    'elemen' => $elemen,
                    'urutan' => $urutan,
                    'is_active' => true,
                    'created_by' => $owner->id,
                ]
            );
            $tps[$kode] = $tp;
        }

        return $tps;
    }

    private function createBindonesiaTPs(object $subject, object $school, $gVII, $gVIII, $ay, User $owner): array
    {
        $tuples = [
            [$gVII, 'TP.BIN.7.01', 'Mengidentifikasi informasi dalam teks narasi', 'Membaca', 1],
            [$gVIII, 'TP.BIN.8.01', 'Menyimpulkan makna teks eksposisi', 'Menyimpulkan', 1],
        ];
        $tps = [];
        foreach ($tuples as [$grade, $kode, $deskripsi, $elemen, $urutan]) {
            $tp = TujuanPembelajaran::firstOrCreate(
                ['kode_tp' => $kode],
                [
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'grade_level_id' => $grade->id,
                    'academic_year_id' => $ay?->id,
                    'semester' => 'ganjil',
                    'fase' => 'E',
                    'deskripsi' => $deskripsi,
                    'elemen' => $elemen,
                    'urutan' => $urutan,
                    'is_active' => true,
                    'created_by' => $owner->id,
                ]
            );
            $tps[$kode] = $tp;
        }

        return $tps;
    }

    private function seedSoal(BankSoal $bank, array $tps, User $owner): void
    {
        $questions = [
            [
                'kode_tp' => 'TP.MTK.7.01', 'tipe' => 'pg', 'pertanyaan' => 'Bilangan mana yang lebih besar: -3 atau -7?',
                'options' => [['A', '-3', true], ['B', '-7', false], ['C', 'Sama besar', false], ['D', 'Tidak bisa ditentukan', false]],
                'bobot' => 4.00,
            ],
            [
                'kode_tp' => 'TP.MTK.7.01', 'tipe' => 'pg', 'pertanyaan' => 'Pecahan 3/4 senilai dengan?',
                'options' => [['A', '6/8', true], ['B', '5/8', false], ['C', '4/9', false], ['D', '7/10', false]],
                'bobot' => 4.00,
            ],
            [
                'kode_tp' => 'TP.MTK.7.02', 'tipe' => 'pg', 'pertanyaan' => '1/2 + 1/3 = ?',
                'options' => [['A', '2/5', false], ['B', '5/6', true], ['C', '4/6', false], ['D', '1/5', false]],
                'bobot' => 5.00,
            ],
            [
                'kode_tp' => 'TP.MTK.8.01', 'tipe' => 'pg', 'pertanyaan' => 'Penyelesaian dari 3x + 6 = 18 adalah x = ?',
                'options' => [['A', '2', false], ['B', '3', false], ['C', '4', true], ['D', '6', false]],
                'bobot' => 6.00,
            ],
            [
                'kode_tp' => 'TP.MTK.8.02', 'tipe' => 'pg', 'pertanyaan' => 'Suku ke-5 barisan aritmetika 2,5,8,11,... adalah?',
                'options' => [['A', '11', false], ['B', '14', true], ['C', '17', false], ['D', '20', false]],
                'bobot' => 8.00,
            ],
            [
                'kode_tp' => 'TP.BIN.7.01', 'tipe' => 'pg', 'pertanyaan' => 'Informasi tersurat dalam teks adalah informasi yang?',
                'options' => [['A', 'Tersebut secara jelas dalam teks', true], ['B', 'Harus ditafsirkan', false], ['C', 'Tidak dalam teks', false], ['D', 'Bersifat opini', false]],
                'bobot' => 4.00,
            ],
            [
                'kode_tp' => 'TP.BIN.8.01', 'tipe' => 'pg', 'pertanyaan' => 'Teks eksposisi bertujuan untuk?',
                'options' => [['A', 'Menghibur', false], ['B', 'Mengajak', false], ['C', 'Memberi informasi & meyakinkan', true], ['D', 'Menceritakan', false]],
                'bobot' => 5.00,
            ],
        ];

        foreach ($questions as $q) {
            $tp = $tps[$q['kode_tp']] ?? null;
            if (! $tp) {
                continue;
            }

            $existing = \App\Models\Soal::where('pertanyaan', $q['pertanyaan'])
                ->where('tp_id', $tp->id)
                ->first();
            if ($existing) {
                continue;
            }

            $hash = app(\App\Services\Evaluasi\ContentHashEngine::class)->hashFromSoal(
                $q['pertanyaan'],
                array_filter(array_column($q['options'], 'is_correct'))
            );

            $soal = \App\Models\Soal::create([
                'bank_soal_id' => $bank->id,
                'tp_id' => $tp->id,
                'tipe_soal' => $q['tipe'],
                'pertanyaan' => $q['pertanyaan'],
                'bobot_default' => $q['bobot'],
                'status' => 'approved',
                'dibuat_oleh' => $owner->id,
                'approved_at' => now(),
                'content_hash' => $hash,
            ]);

            foreach ($q['options'] as $i => $o) {
                \App\Models\SoalOption::create([
                    'soal_id' => $soal->id,
                    'label' => $o[0],
                    'teks_opsi' => $o[1],
                    'is_correct' => $o[2],
                    'urutan' => $i + 1,
                ]);
            }
        }
    }

    private function createKisiKisi(object $school, object $mtk, object $bin, $gVIII, object $ay, User $owner): void
    {
        // Kisi-kisi Matematika
        $kisiMTK = \App\Models\KisiKisiSoal::firstOrCreate(
            ['judul' => 'Kisi-kisi STS Matematika Fase E Kelas VIII Semester Ganjil'],
            [
                'school_id' => $school->id,
                'subject_id' => $mtk->id,
                'grade_level_id' => $gVIII?->id,
                'academic_year_id' => $ay?->id,
                'semester' => 'ganjil',
                'jenis_ujian' => 'sts',
                'tingkat_sekolah' => 'smp',
                'total_soal_target' => 20,
                'total_bobot_target' => 100,
                'is_active' => true,
                'created_by' => $owner->id,
            ]
        );

        if ($kisiMTK->wasRecentlyCreated || $kisiMTK->items->count() === 0) {
            $tps = \App\Models\TujuanPembelajaran::where('subject_id', $mtk->id)->get();
            foreach ($tps as $tp) {
                $levelKognitif = match ($tp->kode_tp) {
                    'TP.MTK.7.01' => 'C1_mengingat',
                    'TP.MTK.7.02' => 'C2_memahami',
                    'TP.MTK.8.01' => 'C3_menerapkan',
                    default => 'C4_menganalisis',
                };
                \App\Models\KisiKisiItem::firstOrCreate(
                    ['kisi_kisi_soal_id' => $kisiMTK->id, 'tp_id' => $tp->id],
                    [
                        'level_kognitif' => $levelKognitif,
                        'jumlah_soal' => 4,
                        'bobot_per_soal' => 5.00,
                    ]
                );
            }
        }

        // Kisi-kisi B. Indonesia
        $kisiBin = \App\Models\KisiKisiSoal::firstOrCreate(
            ['judul' => 'Kisi-kisi SAKE Bahasa Indonesia Fase E Kelas VIII'],
            [
                'school_id' => $school->id,
                'subject_id' => $bin->id,
                'grade_level_id' => $gVIII?->id,
                'academic_year_id' => $ay?->id,
                'semester' => 'genap',
                'jenis_ujian' => 'sas',
                'tingkat_sekolah' => 'smp',
                'total_soal_target' => 10,
                'total_bobot_target' => 50,
                'is_active' => true,
                'created_by' => $owner->id,
            ]
        );

        $tpsBin = \App\Models\TujuanPembelajaran::where('subject_id', $bin->id)->get();
        foreach ($tpsBin as $tp) {
            \App\Models\KisiKisiItem::firstOrCreate(
                ['kisi_kisi_soal_id' => $kisiBin->id, 'tp_id' => $tp->id],
                [
                    'level_kognitif' => 'C2_memahami',
                    'jumlah_soal' => 3,
                    'bobot_per_soal' => 5.00,
                ]
            );
        }

        $this->command->info(sprintf(
            '[KisiKisiSeeder] kisi_kisi=%d, kisi_items=%d',
            \App\Models\KisiKisiSoal::count(),
            \App\Models\KisiKisiItem::count()
        ));
    }
}
