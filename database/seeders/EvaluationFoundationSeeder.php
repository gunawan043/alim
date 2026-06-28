<?php

namespace Database\Seeders;

use App\Models\BankSoal;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use App\Services\Evaluasi\ContentHashEngine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EvaluationFoundationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $school = $this->firstOrCreateSchool();
            [$subjectMatematika, $subjectBindonesia] = $this->firstOrCreateSubjects();
            [$gradeVII, $gradeVIII] = $this->firstOrCreateGradeLevels();
            $academicYear = $this->firstOrCreateAcademicYear($school);
            $owner = $this->firstOrCreateOwner($school);

            $tpMatematikaList = $this->seedMatematikaTPs($subjectMatematika, $school, $gradeVII, $gradeVIII, $academicYear, $owner);
            $tpBindonesiaList = $this->seedBindonesiaTPs($subjectBindonesia, $school, $gradeVII, $gradeVIII, $academicYear, $owner);

            $bankMatematika = $this->firstOrCreateBankSoal($school, $subjectMatematika, 'E', $owner, 'Bank Soal Matematika Fase E — Audit 2026-06-19');
            $bankBindonesia = $this->firstOrCreateBankSoal($school, $subjectBindonesia, 'E', $owner, 'Bank Soal Bahasa Indonesia Fase E — Audit 2026-06-19');

            $this->seedMatematikaSoal($bankMatematika, $tpMatematikaList, $owner);
            $this->seedBindonesiaSoal($bankBindonesia, $tpBindonesiaList, $owner);

            $this->command->info(sprintf(
                '[EvaluationFoundationSeeder] bank_soal=%d, tujuan_pembelajaran=%d, soal=%d, soal_options=%d',
                BankSoal::withoutTrashed()->count(),
                TujuanPembelajaran::withoutTrashed()->count(),
                Soal::withoutTrashed()->count(),
                SoalOption::withoutTrashed()->count()
            ));
        });
    }

    private function firstOrCreateSchool(): School
    {
        return School::firstOrCreate(
            ['code' => 'PUSAT'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Pondok Pesantren Pusat (Audit Seed)',
                'level' => 'smp',
                'address' => 'Alamat Pusat Audit',
            ]
        );
    }

    private function firstOrCreateSubjects(): array
    {
        $mat = Subject::firstOrCreate(
            ['code' => 'MTK'],
            ['id' => (string) Str::uuid(), 'name' => 'Matematika']
        );
        $bin = Subject::firstOrCreate(
            ['code' => 'BIN'],
            ['id' => (string) Str::uuid(), 'name' => 'Bahasa Indonesia']
        );

        return [$mat, $bin];
    }

    private function firstOrCreateGradeLevels(): array
    {
        $tableCandidates = ['grade_levels', 'levels', 'classes'];
        $gradeTable = null;
        foreach ($tableCandidates as $t) {
            if (Schema::hasTable($t)) {
                $gradeTable = $t;
                break;
            }
        }

        if (! $gradeTable) {
            return [null, null];
        }

        $row = DB::table($gradeTable);
        $columns = Schema::getColumnListing($gradeTable);

        $gradeVii = $this->firstOrCreateGeneric($row, $columns, ['name' => 'VII'], ['name' => 'VII', 'level' => 7]);
        $gradeViii = $this->firstOrCreateGeneric($row, $columns, ['name' => 'VIII'], ['name' => 'VIII', 'level' => 8]);

        return [$gradeVii->id ?? null, $gradeViii->id ?? null];
    }

    private function firstOrCreateGeneric($builder, array $columns, array $where, array $payload): object
    {
        $existing = $builder->where($where)->first();
        if ($existing) {
            return $existing;
        }
        $insert = array_intersect_key($payload, array_flip($columns));
        if (! in_array('id', $columns, true) && Schema::hasColumn($builder->getModel()->getTable() ?? '', 'id')) {
            $insert['id'] = (string) Str::uuid();
        }
        $builder->insert($insert);

        return $builder->where($where)->first();
    }

    private function firstOrCreateAcademicYear(School $school)
    {
        $table = 'academic_years';
        if (! Schema::hasTable($table)) {
            return null;
        }
        $columns = Schema::getColumnListing($table);
        $payload = [
            'school_id' => $school->id,
            'name' => '2026/2027',
            'semester' => 'ganjil',
        ];
        $builder = DB::table($table);
        $existing = $builder->where(['school_id' => $school->id, 'name' => '2026/2027'])->first();
        if ($existing) {
            return $existing;
        }
        $insert = array_intersect_key($payload, array_flip($columns));
        if (in_array('id', $columns, true) && (! isset($insert['id']) || ! $insert['id'])) {
            $insert['id'] = (string) Str::uuid();
        }
        $builder->insert($insert);

        return $builder->where(['school_id' => $school->id, 'name' => '2026/2027'])->first();
    }

    private function firstOrCreateOwner(School $school): User
    {
        return User::firstOrCreate(
            ['email' => 'audit-tp@alim.local'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Audit TP Owner',
                'password' => bcrypt('audit-tp-2026'),
                'school_id' => $school->id,
            ]
        );
    }

    private function firstOrCreateBankSoal(School $school, Subject $subject, string $fase, User $owner, string $name): BankSoal
    {
        return BankSoal::withoutTrashed()
            ->where('school_id', $school->id)
            ->where('subject_id', $subject->id)
            ->where('fase', $fase)
            ->where('owner_user_id', $owner->id)
            ->where('name', $name)
            ->first() ?? BankSoal::create([
                'school_id' => $school->id,
                'subject_id' => $subject->id,
                'fase' => $fase,
                'name' => $name,
                'jenis_soal' => 'campuran',
                'tingkat_kesulitan_target' => 'campuran',
                'is_public' => true,
                'owner_user_id' => $owner->id,
                'allow_cross_teacher_clone' => true,
            ]);
    }

    private function seedMatematikaTPs(Subject $subject, School $school, $gradeVii, $gradeViii, $academicYear, User $owner): array
    {
        $items = [
            ['VII', 'C1', 'TP.MTK.7.01', 'Peserta didik mampu membandingkan bilangan bulat dan pecahan sederhana.'],
            ['VII', 'C2', 'TP.MTK.7.02', 'Peserta didik mampu menjelaskan operasi penjumlahan dan pengurangan pecahan.'],
            ['VIII', 'C3', 'TP.MTK.8.01', 'Peserta didik mampu menyelesaikan persamaan linear satu variabel.'],
            ['VIII', 'C4', 'TP.MTK.8.02', 'Peserta didik mampu menganalisis pola bilangan dan barisan aritmetika.'],
        ];

        $result = [];
        foreach ($items as [$gradeKey, $level, $kode, $deskripsi]) {
            $gradeId = $gradeKey === 'VII' ? $gradeVii : $gradeViii;
            $row = TujuanPembelajaran::withoutTrashed()
                ->where('subject_id', $subject->id)
                ->where('school_id', $school->id)
                ->where('kode_tp', $kode)
                ->first();
            if (! $row) {
                $row = TujuanPembelajaran::create([
                    'subject_id' => $subject->id,
                    'school_id' => $school->id,
                    'grade_level_id' => $gradeId,
                    'academic_year_id' => $academicYear->id ?? null,
                    'semester' => 'ganjil',
                    'fase' => 'E',
                    'kode_tp' => $kode,
                    'deskripsi' => $deskripsi,
                    'elemen' => 'Pemahaman Konsep',
                    'alokasi_waktu' => 90,
                    'urutan' => $level === 'C1' ? 1 : 2,
                    'is_active' => true,
                    'created_by' => $owner->id,
                ]);
            }
            $result[$kode] = $row;
        }

        return $result;
    }

    private function seedBindonesiaTPs(Subject $subject, School $school, $gradeVii, $gradeViii, $academicYear, User $owner): array
    {
        $items = [
            ['VII', 'TP.BIN.7.01', 'Peserta didik mampu mengidentifikasi informasi tersurat dalam teks narasi.'],
            ['VIII', 'TP.BIN.8.01', 'Peserta didik mampu menyimpulkan makna teks eksposisi.'],
        ];

        $result = [];
        foreach ($items as [$gradeKey, $kode, $deskripsi]) {
            $gradeId = $gradeKey === 'VII' ? $gradeVii : $gradeViii;
            $row = TujuanPembelajaran::withoutTrashed()
                ->where('subject_id', $subject->id)
                ->where('school_id', $school->id)
                ->where('kode_tp', $kode)
                ->first();
            if (! $row) {
                $row = TujuanPembelajaran::create([
                    'subject_id' => $subject->id,
                    'school_id' => $school->id,
                    'grade_level_id' => $gradeId,
                    'academic_year_id' => $academicYear->id ?? null,
                    'semester' => 'ganjil',
                    'fase' => 'E',
                    'kode_tp' => $kode,
                    'deskripsi' => $deskripsi,
                    'elemen' => 'Membaca',
                    'alokasi_waktu' => 90,
                    'urutan' => 1,
                    'is_active' => true,
                    'created_by' => $owner->id,
                ]);
            }
            $result[$kode] = $row;
        }

        return $result;
    }

    private function seedMatematikaSoal(BankSoal $bank, array $tpMap, User $owner): void
    {
        $questions = [
            [
                'kode_tp' => 'TP.MTK.7.01',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Bilangan mana yang lebih besar: -3 atau -7?',
                'options' => [
                    ['label' => 'A', 'teks' => '-3', 'is_correct' => true],
                    ['label' => 'B', 'teks' => '-7', 'is_correct' => false],
                    ['label' => 'C', 'teks' => 'Sama besar', 'is_correct' => false],
                    ['label' => 'D', 'teks' => 'Tidak bisa ditentukan', 'is_correct' => false],
                ],
                'bobot' => 4.00,
                'kesulitan' => 'mudah',
            ],
            [
                'kode_tp' => 'TP.MTK.7.01',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Pecahan 3/4 senilai dengan?',
                'options' => [
                    ['label' => 'A', 'teks' => '6/8', 'is_correct' => true],
                    ['label' => 'B', 'teks' => '5/8', 'is_correct' => false],
                    ['label' => 'C', 'teks' => '4/9', 'is_correct' => false],
                    ['label' => 'D', 'teks' => '7/10', 'is_correct' => false],
                ],
                'bobot' => 4.00,
                'kesulitan' => 'mudah',
            ],
            [
                'kode_tp' => 'TP.MTK.7.02',
                'tipe_soal' => 'pg',
                'pertanyaan' => '1/2 + 1/3 = ?',
                'options' => [
                    ['label' => 'A', 'teks' => '2/5', 'is_correct' => false],
                    ['label' => 'B', 'teks' => '5/6', 'is_correct' => true],
                    ['label' => 'C', 'teks' => '4/6', 'is_correct' => false],
                    ['label' => 'D', 'teks' => '1/5', 'is_correct' => false],
                ],
                'bobot' => 5.00,
                'kesulitan' => 'sedang',
            ],
            [
                'kode_tp' => 'TP.MTK.7.02',
                'tipe_soal' => 'pg',
                'pertanyaan' => '3/4 - 1/8 = ?',
                'options' => [
                    ['label' => 'A', 'teks' => '5/8', 'is_correct' => true],
                    ['label' => 'B', 'teks' => '2/4', 'is_correct' => false],
                    ['label' => 'C', 'teks' => '1/2', 'is_correct' => false],
                    ['label' => 'D', 'teks' => '3/8', 'is_correct' => false],
                ],
                'bobot' => 5.00,
                'kesulitan' => 'sedang',
            ],
            [
                'kode_tp' => 'TP.MTK.8.01',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Penyelesaian dari 3x + 6 = 18 adalah x = ?',
                'options' => [
                    ['label' => 'A', 'teks' => '2', 'is_correct' => false],
                    ['label' => 'B', 'teks' => '3', 'is_correct' => false],
                    ['label' => 'C', 'teks' => '4', 'is_correct' => true],
                    ['label' => 'D', 'teks' => '6', 'is_correct' => false],
                ],
                'bobot' => 6.00,
                'kesulitan' => 'sedang',
            ],
            [
                'kode_tp' => 'TP.MTK.8.01',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Jika 5x - 10 = 0 maka x = ?',
                'options' => [
                    ['label' => 'A', 'teks' => '0', 'is_correct' => false],
                    ['label' => 'B', 'teks' => '2', 'is_correct' => true],
                    ['label' => 'C', 'teks' => '5', 'is_correct' => false],
                    ['label' => 'D', 'teks' => '10', 'is_correct' => false],
                ],
                'bobot' => 6.00,
                'kesulitan' => 'mudah',
            ],
            [
                'kode_tp' => 'TP.MTK.8.01',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Nilai x yang memenuhi 2x + 3 = x + 7 adalah?',
                'options' => [
                    ['label' => 'A', 'teks' => '2', 'is_correct' => false],
                    ['label' => 'B', 'teks' => '3', 'is_correct' => false],
                    ['label' => 'C', 'teks' => '4', 'is_correct' => true],
                    ['label' => 'D', 'teks' => '5', 'is_correct' => false],
                ],
                'bobot' => 6.00,
                'kesulitan' => 'sulit',
            ],
            [
                'kode_tp' => 'TP.MTK.8.02',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Suku ke-5 barisan aritmetika 2, 5, 8, 11, … adalah?',
                'options' => [
                    ['label' => 'A', 'teks' => '11', 'is_correct' => false],
                    ['label' => 'B', 'teks' => '14', 'is_correct' => true],
                    ['label' => 'C', 'teks' => '17', 'is_correct' => false],
                    ['label' => 'D', 'teks' => '20', 'is_correct' => false],
                ],
                'bobot' => 8.00,
                'kesulitan' => 'sedang',
            ],
            [
                'kode_tp' => 'TP.MTK.8.02',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Beda barisan aritmetika 3, 7, 11, 15, … adalah?',
                'options' => [
                    ['label' => 'A', 'teks' => '2', 'is_correct' => false],
                    ['label' => 'B', 'teks' => '3', 'is_correct' => false],
                    ['label' => 'C', 'teks' => '4', 'is_correct' => true],
                    ['label' => 'D', 'teks' => '5', 'is_correct' => false],
                ],
                'bobot' => 8.00,
                'kesulitan' => 'mudah',
            ],
            [
                'kode_tp' => 'TP.MTK.8.02',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Jumlah 10 suku pertama barisan 1, 3, 5, 7, … dengan rumus Sn = n² adalah?',
                'options' => [
                    ['label' => 'A', 'teks' => '50', 'is_correct' => false],
                    ['label' => 'B', 'teks' => '100', 'is_correct' => true],
                    ['label' => 'C', 'teks' => '25', 'is_correct' => false],
                    ['label' => 'D', 'teks' => '10', 'is_correct' => false],
                ],
                'bobot' => 10.00,
                'kesulitan' => 'sulit',
            ],
        ];

        foreach ($questions as $q) {
            $tp = $tpMap[$q['kode_tp']] ?? null;
            if (! $tp) {
                continue;
            }
            $this->createSoalWithOptions($bank, $tp, $owner, $q);
        }
    }

    private function seedBindonesiaSoal(BankSoal $bank, array $tpMap, User $owner): void
    {
        $questions = [
            [
                'kode_tp' => 'TP.BIN.7.01',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Informasi tersurat dalam teks adalah informasi yang?',
                'options' => [
                    ['label' => 'A', 'teks' => 'Tersebut secara jelas dalam teks', 'is_correct' => true],
                    ['label' => 'B', 'teks' => 'Harus ditafsirkan sendiri', 'is_correct' => false],
                    ['label' => 'C', 'teks' => 'Tidak terdapat dalam teks', 'is_correct' => false],
                    ['label' => 'D', 'teks' => 'Bersifat opini', 'is_correct' => false],
                ],
                'bobot' => 4.00,
                'kesulitan' => 'mudah',
            ],
            [
                'kode_tp' => 'TP.BIN.8.01',
                'tipe_soal' => 'pg',
                'pertanyaan' => 'Teks eksposisi bertujuan untuk?',
                'options' => [
                    ['label' => 'A', 'teks' => 'Menghibur pembaca', 'is_correct' => false],
                    ['label' => 'B', 'teks' => 'Mengajak pembaca', 'is_correct' => false],
                    ['label' => 'C', 'teks' => 'Memberi informasi dan meyakinkan', 'is_correct' => true],
                    ['label' => 'D', 'teks' => 'Menceritakan pengalaman', 'is_correct' => false],
                ],
                'bobot' => 5.00,
                'kesulitan' => 'sedang',
            ],
        ];

        foreach ($questions as $q) {
            $tp = $tpMap[$q['kode_tp']] ?? null;
            if (! $tp) {
                continue;
            }
            $this->createSoalWithOptions($bank, $tp, $owner, $q);
        }
    }

    private function createSoalWithOptions(BankSoal $bank, TujuanPembelajaran $tp, User $owner, array $q): void
    {
        $correctTexts = collect($q['options'])
            ->filter(fn ($o) => $o['is_correct'])
            ->pluck('teks')
            ->implode('|');
        $hash = app(ContentHashEngine::class)->hashFromSoal($q['pertanyaan'], explode('|', $correctTexts));

        $existing = Soal::withoutTrashed()->where('content_hash', $hash)->first();
        if ($existing) {
            return;
        }

        $soal = Soal::create([
            'bank_soal_id' => $bank->id,
            'tp_id' => $tp->id,
            'tipe_soal' => $q['tipe_soal'],
            'pertanyaan' => $q['pertanyaan'],
            'bobot_default' => $q['bobot'],
            'tingkat_kesulitan_estimasi' => $q['kesulitan'],
            'waktu_estimasi_menit' => 2,
            'status' => 'approved',
            'dibuat_oleh' => $owner->id,
            'direview_oleh' => $owner->id,
            'approved_by' => $owner->id,
            'approved_at' => now(),
            'tags' => ['seed', 'audit-2026-06-19', $tp->kode_tp],
            'content_hash' => $hash,
        ]);

        foreach ($q['options'] as $idx => $opt) {
            SoalOption::create([
                'soal_id' => $soal->id,
                'label' => $opt['label'],
                'teks_opsi' => $opt['teks'],
                'is_correct' => $opt['is_correct'],
                'urutan' => $idx + 1,
            ]);
        }
    }
}
