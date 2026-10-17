<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\KisiKisiSoal;
use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkSheet;
use App\Models\PaketSoal;
use App\Models\PaketSoalItem;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\Evaluasi\PaketNaskahService;
use App\Services\Ljk\LjkExamService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder LJK untuk GTK: Lalu Sopian Harianto — contoh mapel Bahasa Inggris, kelas 1B.
 *
 * Menyiapkan seluruh prasyarat agar GTK tersebut dapat menerbitkan LJK:
 *   1. Buku nilai (mapel × rombel × TA × semester) — dipakai bila sudah ada, dibuat bila belum.
 *   2. Bank Soal + soal PG + opsi jawaban (idempoten, hash konten deterministik).
 *   3. Kisi-kisi (jenis ujian STS).
 *   4. Paket Soal FINAL: butir dibekukan (snapshot naskah) lalu dipublikasikan,
 *      sehingga muncul sebagai pilihan di menu LJK Smart Scanner.
 *   5. Satu ujian LJK contoh (peserta + lembar siap cetak) bila belum ada.
 *
 * Idempoten — aman dijalankan berulang:
 *   php artisan db:seed --class=LjkLaluSopianSeeder
 *
 * Catatan: bila rombel contoh masih kosong, seeder membuat 10 santri demo
 * (NISN berawalan "SEED-") agar lembar bisa diterbitkan. Rombel berisi
 * (seperti 1B saat ini) tidak akan diubah.
 */
class LjkLaluSopianSeeder extends Seeder
{
    private const TEACHER_EMAIL = 'hariantosopian507@gmail.com';

    private const TEACHER_NAME_HINT = 'SOPIAN HARIANTO';

    private const SUBJECT_NAME = 'Bahasa Inggris';

    private const GROUP_NAME = '1B';

    private const SEMESTER = 'ganjil';

    private const JENIS_UJIAN = 'sts';

    private const JUMLAH_SOAL = 23;

    private const KKM = 70;

    private const DEMO_STUDENTS = 10;

    public function run(): void
    {
        $user = User::query()
            ->where('email', self::TEACHER_EMAIL)
            ->orWhere('name', 'like', '%'.self::TEACHER_NAME_HINT.'%')
            ->first();

        if (! $user) {
            $this->command?->error('GTK "Lalu Sopian Harianto" tidak ditemukan — seeder dihentikan.');
            $this->command?->line('  Buat akun GTK-nya lebih dahulu (menu Data GTK), lalu jalankan ulang seeder ini.');

            return;
        }

        $book = $this->resolveBook($user);

        if (! $book) {
            $this->command?->error("Tidak ada buku nilai aktif untuk {$user->name} — seeder dihentikan.");

            return;
        }

        $subject = $book->subject;
        $group = $book->studyGroup;
        $grade = $group?->gradeLevel;
        $school = $book->school;

        if (! $subject || ! $group || ! $grade || ! $school) {
            $this->command?->error('Buku nilai tidak lengkap (mapel/rombel/tingkat/sekolah) — seeder dihentikan.');

            return;
        }

        $this->command?->info('=== LJK Seeder — '.$user->name.' ===');
        $this->command?->line("  Mapel        : {$subject->name}");
        $this->command?->line("  Rombel       : {$group->name} ({$grade->name})");
        $this->command?->line("  Sekolah      : {$school->name}");
        $this->command?->line("  Tahun/Semester: {$book->academicYear?->name} / {$book->semester}");
        $this->command?->newLine();

        // ── 1. Bank Soal + soal + opsi ────────────────────────────────────
        $bank = BankSoal::firstOrCreate(
            [
                'school_id' => $school->id,
                'subject_id' => $subject->id,
                'grade_level_id' => $grade->id,
                'academic_year_id' => $book->academic_year_id,
                'semester' => $book->semester,
                'nama' => 'Bank Soal '.$subject->name.' — '.$grade->name,
            ],
            [
                'jenis_soal' => 'pilihan_ganda',
                'deskripsi' => 'Bank soal contoh untuk LJK (Bahasa Inggris kelas 1).',
                'owner_user_id' => $user->id,
                'created_by' => $user->id,
                'is_public' => false,
                'shared_scope' => 'internal_school',
                'total_soal' => 0,
            ]
        );

        $questions = $this->questions();
        $soalBaru = 0;
        $soalList = collect();

        foreach ($questions as $index => $item) {
            $urutan = $index + 1;
            $hash = hash('sha256', 'ljk-seed-bing-1b|'.$bank->id.'|'.$urutan);

            $soal = Soal::firstOrCreate(
                ['content_hash' => $hash],
                [
                    'bank_soal_id' => $bank->id,
                    'tipe_soal' => 'pg',
                    'pertanyaan' => $item['q'],
                    'bobot_default' => 1,
                    'tingkat_kesulitan_estimasi' => 'mudah',
                    'status' => 'approved',
                    'workflow_status' => Soal::WORKFLOW_APPROVED,
                    'dibuat_oleh' => $user->id,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]
            );

            if ($soal->wasRecentlyCreated) {
                $soalBaru++;

                foreach ($item['options'] as $optIndex => $text) {
                    SoalOption::create([
                        'soal_id' => $soal->id,
                        'label' => chr(65 + $optIndex),
                        'teks_opsi' => $text,
                        'is_correct' => $optIndex === $item['key'],
                        'urutan' => $optIndex + 1,
                    ]);
                }
            }

            $soalList->push($soal);
        }

        $bank->forceFill(['total_soal' => $soalList->count()])->save();

        // ── Wacana / soal cerita (contoh format) ──────────────────────────
        $wacana = $this->seedWacana($bank, $user, $soalList);
        $wacanaDipakai = $wacana['dipakai'];

        // ── 2. Kisi-kisi ──────────────────────────────────────────────────
        $kisi = KisiKisiSoal::firstOrCreate(
            [
                'school_id' => $school->id,
                'subject_id' => $subject->id,
                'grade_level_id' => $grade->id,
                'academic_year_id' => $book->academic_year_id,
                'semester' => $book->semester,
                'jenis_ujian' => self::JENIS_UJIAN,
                'judul' => 'Kisi-Kisi STS '.$subject->name.' '.$grade->name,
            ],
            [
                'created_by' => $user->id,
                'deskripsi' => 'Kisi-kisi contoh untuk penerbitan LJK.',
                'tingkat_sekolah' => $school->school_level ?? 'sd',
                'total_soal_target' => $soalList->count(),
                'total_bobot_target' => $soalList->count(),
                'is_active' => true,
                'approved_at' => now(),
            ]
        );

        // ── 3. Paket Soal final (naskah beku + published) ─────────────────
        // Kode paket harus bersih dari karakter non-aman URL (mis. "2026/2027").
        $tahunAjaran = preg_replace('/[^A-Za-z0-9]+/', '-', (string) ($book->academicYear?->name ?? 'TA')) ?? 'TA';
        $kodePaket = 'PKT-BING-'.strtoupper($group->name).'-'.trim($tahunAjaran, '-');

        $paket = PaketSoal::firstOrCreate(
            ['kode_paket' => $kodePaket],
            [
                'kisi_kisi_soal_id' => $kisi->id,
                'judul' => 'Paket '.strtoupper(self::JENIS_UJIAN).' '.$subject->name.' '.$group->name,
                'versi' => 1,
                'is_acak_urutan_soal' => false,
                'is_acak_opsi' => false,
                'waktu_pengerjaan_menit' => 60,
                'shared_scope' => 'internal_school',
                'instruksi_umum' => 'Pilih satu jawaban dengan tanda silang (X) memakai pulpen hitam atau biru.',
                'kkm' => self::KKM,
            ]
        );

        $existingSoalIds = $paket->items()->pluck('soal_id')->all();
        $itemsChanged = false;

        $soalList->values()->each(function (Soal $soal, int $i) use ($paket, $existingSoalIds, &$itemsChanged) {
            if (in_array($soal->id, $existingSoalIds, true)) {
                return;
            }

            PaketSoalItem::create([
                'paket_soal_id' => $paket->id,
                'soal_id' => $soal->id,
                'urutan' => $i + 1,
                'nomor' => $i + 1,
                'jenis_soal' => 'pg',
            ]);
            $itemsChanged = true;
        });

        $paket->recomputeTotals();

        // Butir berubah → beku ulang naskah agar wacana & soal baru terikut.
        if (! $paket->hasSnapshot() || $itemsChanged) {
            app(PaketNaskahService::class)->snapshot($paket);
        }

        if (! $paket->isFinal()) {
            $paket->forceFill([
                'workflow_status' => PaketSoal::WORKFLOW_APPROVED,
                'approved_at' => now(),
                'approved_by' => $user->id,
            ])->save();

            $paket->publish($user->id);
        }

        // ── 4. Santri demo bila rombel masih kosong ───────────────────────
        $riwayatKelas = StudentClassHistory::query()
            ->where('study_group_id', $group->id)
            ->where('academic_year_id', $book->academic_year_id)
            ->where('is_active', true);

        $jumlahSantri = $riwayatKelas->count();
        $santriBaru = 0;

        if ($jumlahSantri === 0) {
            for ($i = 1; $i <= self::DEMO_STUDENTS; $i++) {
                $student = Student::firstOrCreate(
                    ['nisn' => 'SEED-BING-1B-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)],
                    [
                        'name' => 'Santri Demo 1B '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                        'nis' => 'SEED1B'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                        'gender' => $i % 2 === 1 ? 'L' : 'P',
                        'school_id' => $school->id,
                        'status' => 'active',
                    ]
                );

                StudentClassHistory::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'study_group_id' => $group->id,
                        'academic_year_id' => $book->academic_year_id,
                    ],
                    [
                        'attendance_number' => $i,
                        'is_active' => true,
                    ]
                );

                if ($student->wasRecentlyCreated) {
                    $santriBaru++;
                }
            }

            $jumlahSantri = self::DEMO_STUDENTS;
            $this->command?->warn('  Rombel masih kosong — dibuat '.$santriBaru.' santri demo (NISN "SEED-…").');
        }

        // ── 5. Ujian LJK contoh (peserta + lembar) ────────────────────────
        $examService = app(LjkExamService::class);

        $exam = LjkExam::query()
            ->where('paket_soal_id', $paket->id)
            ->where('admin_book_id', $book->id)
            ->first();

        $lembar = 0;

        if (! $exam) {
            $exam = $examService->createExam([
                'judul' => 'STS '.$subject->name.' '.$group->name,
                'paket_soal_id' => $paket->id,
                'admin_book_id' => $book->id,
                'study_group_id' => $group->id,
                'jenis_ujian' => self::JENIS_UJIAN,
                'semester' => $book->semester,
                'tanggal' => now()->toDateString(),
                'waktu_mulai' => '07:30',
                'waktu_selesai' => '09:00',
                // Mode fotokopi: satu lembar master tanpa nama berlaku untuk semua santri.
                'canonical_order' => true,
            ], $user);

            $examService->provisionParticipants($exam);
            $lembar = $examService->generateSheets($exam, $user);
        }

        // ── Ringkasan ─────────────────────────────────────────────────────
        $this->command?->newLine();
        $this->command?->info('Selesai. Ringkasan:');
        $this->command?->table(['Item', 'Nilai'], [
            ['GTK', $user->name],
            ['Buku nilai', $subject->name.' · '.$group->name.' · '.$book->semester],
            ['Bank soal', $bank->nama.' ('.$soalList->count().' soal, '.$soalBaru.' baru)'],
            ['Kisi-kisi', $kisi->judul],
            ['Wacana contoh', $wacana['judul'].' (soal '.implode(', ', array_map(fn ($w) => $w['rentang'], $wacanaDipakai)).')'],
            ['Paket soal', $paket->kode_paket.' ('.$paket->jumlah_soal_aktual.' butir, final)'],
            ['Santri di rombel', $jumlahSantri],
            ['Ujian LJK', $exam->code.' ('.($exam->status).')'],
            ['Lembar', LjkSheet::where('ljk_exam_id', $exam->id)->count().' lembar'.
                ($lembar > 0 ? " ({$lembar} baru dibuat)" : ' (sudah ada)')],
            ['Peserta LJK', LjkParticipant::where('ljk_exam_id', $exam->id)->count().' peserta'],
        ]);

        $this->command?->newLine();
        $this->command?->line('Langkah berikutnya (login sebagai '.$user->email.'):');
        $this->command?->line('  1. Buka menu Bank Soal & Asesmen → LJK Smart Scanner.');
        $this->command?->line('  2. Halaman "Terbitkan LJK" → pilih buku nilai Bahasa Inggris 1B dan paket '.$paket->kode_paket.'.');
        $this->command?->line('  3. Ujian '.$exam->code.' sudah dibuat: cetak PDF lembar, lalu pindai dengan kamera HP.');
    }

    /**
     * Buku nilai target: Bahasa Inggris × 1B (fallback: buku Bahasa Inggris lain).
     */
    private function resolveBook(User $user): ?TeacherAdminBook
    {
        $base = TeacherAdminBook::query()
            ->with(['subject', 'studyGroup.gradeLevel', 'school', 'academicYear'])
            ->where('teacher_id', $user->id)
            ->where('is_active', true);

        return (clone $base)
            ->whereHas('subject', fn ($q) => $q->where('name', 'like', '%'.self::SUBJECT_NAME.'%'))
            ->whereHas('studyGroup', fn ($q) => $q->where('name', self::GROUP_NAME))
            ->first()
            ?? (clone $base)
                ->whereHas('subject', fn ($q) => $q->where('name', 'like', '%'.self::SUBJECT_NAME.'%'))
                ->orderBy('created_at')
                ->first()
            ?? $base->orderBy('created_at')->first();
    }

    /**
     * Buat dua wacana contoh: bacaan Inggris (soal 11–13) dan teks Arab
     * (soal 21–23), lalu tautkan ke soal terkait.
     *
     * @param  \Illuminate\Support\Collection<int, Soal>  $soalList
     * @return array{judul: string, dipakai: array<int, array{judul: string, rentang: string}>}
     */
    private function seedWacana(\App\Models\BankSoal $bank, \App\Models\User $user, $soalList): array
    {
        $english = \App\Models\WacanaSoal::firstOrCreate(
            ['bank_soal_id' => $bank->id, 'judul' => 'Aisyah\'s Day (contoh wacana)'],
            [
                'school_id' => $bank->school_id,
                'teks' => 'Aisyah is a student of class one. Every morning she wakes up at five o\'clock. '
                    .'She takes a bath, wears her uniform, and has breakfast with her family. '
                    .'At seven o\'clock she goes to school with her father. She is never late.',
                'sumber' => 'Contoh bacaan pendek kelas 1',
                'created_by' => $user->id,
            ]
        );

        $arabic = \App\Models\WacanaSoal::firstOrCreate(
            ['bank_soal_id' => $bank->id, 'judul' => 'الحِوَار (contoh wacana Arab)'],
            [
                'school_id' => $bank->school_id,
                'teks' => 'أَحْمَدُ تِلْمِيذٌ. هُوَ يَذْهَبُ إِلَى المَدْرَسَةِ صَبَاحًا. '
                    .'يَقْرَأُ الكِتَابَ فِي الفَصْلِ ثُمَّ يَعُودُ إِلَى البَيْتِ. هُوَ طَالِبٌ مُجْتَهِدٌ.',
                'sumber' => 'Contoh teks Arab tanpa harakat lengkap',
                'created_by' => $user->id,
            ]
        );

        // Soal 11–13 memakai bacaan Inggris; soal 21–23 memakai teks Arab.
        $map = [10, 11, 12];
        foreach ($map as $index) {
            if (isset($soalList[$index])) {
                $soalList[$index]->forceFill(['wacana_id' => $english->id])->saveQuietly();
            }
        }

        foreach ([20, 21, 22] as $index) {
            if (isset($soalList[$index])) {
                $soalList[$index]->forceFill(['wacana_id' => $arabic->id])->saveQuietly();
            }
        }

        return [
            'judul' => 'Aisyah\'s Day + الحِوَار',
            'dipakai' => [
                ['judul' => 'Aisyah\'s Day (Inggris)', 'rentang' => '11–13'],
                ['judul' => 'الحِوَار (Arab)', 'rentang' => '21–23'],
            ],
        ];
    }

    /**
     * 23 soal PG: 20 Bahasa Inggris + 3 contoh soal Bahasa Arab (format ponpes).
     *
     * @return array<int, array{q:string, options:array<int,string>, key:int}>
     */
    private function questions(): array
    {
        $items = [
            ['The color of the sky is ...', ['Blue', 'Black', 'Brown', 'Pink']],
            ['One, two, ...', ['Four', 'Three', 'Five', 'Six']],
            ['We say "Good morning" in the ...', ['Evening', 'Night', 'Morning', 'Afternoon']],
            ['The number after four is ...', ['Seven', 'Six', 'Three', 'Five']],
            ['We use our ... to see.', ['Ears', 'Eyes', 'Nose', 'Hands']],
            ['A cow says ...', ['Meow', 'Moo', 'Woof', 'Tweet']],
            ['The color of a banana is ...', ['Purple', 'Green', 'Yellow', 'Red']],
            ['We use our ... to hear.', ['Ears', 'Eyes', 'Mouth', 'Feet']],
            ['How many fingers are on one hand?', ['Three', 'Four', 'Six', 'Five']],
            ['We say "Good ..." before sleeping at night.', ['Morning', 'Night', 'Noon', 'Afternoon']],
            ['An apple is a ...', ['Vegetable', 'Drink', 'Fruit', 'Animal']],
            ['The number before seven is ...', ['Eight', 'Five', 'Six', 'Nine']],
            ['We drink ... when we are thirsty.', ['Rice', 'Water', 'Bread', 'Paper']],
            ['The color of the grass is ...', ['Blue', 'Green', 'Grey', 'Orange']],
            ['A cat says ...', ['Moo', 'Meow', 'Quack', 'Roar']],
            ['Mom and Dad are my ...', ['Friends', 'Teachers', 'Parents', 'Neighbors']],
            ['We say ... when someone gives us something.', ['Sorry', 'Thank you', 'Goodbye', 'Hello']],
            ['The first day of the week is ...', ['Monday', 'Friday', 'Saturday', 'Sunday']],
            ['We go to ... to study.', ['Market', 'School', 'Beach', 'Hospital']],
            ['The color of milk is ...', ['White', 'Black', 'Blue', 'Green']],
            // ── Contoh soal Bahasa Arab (tanpa harakat lengkap) ─────────────
            ['مَا اسْمُ هَذَا الشَّيْءُ؟ (gambar buku)', ['كِتَابٌ', 'قَلَمٌ', 'كُرْسِيٌّ', 'بَابٌ']],
            ['أَحْمَدُ تِلْمِيذٌ مُجْتَهِدٌ. هُوَ ... إِلَى المَدْرَسَةِ صَبَاحًا.', ['يَذْهَبُ', 'يَأْكُلُ', 'يَنَامُ', 'يَلْعَبُ']],
            ['الكِتَابُ فِي الفَصْلِ. مَعْنَى الفَصْلِ ...', ['Kelas', 'Kantor', 'Perpustakaan', 'Kamar']],
        ];

        $out = [];
        foreach ($items as $i => [$q, $options]) {
            $out[] = [
                'q' => $q,
                'options' => $options,
                'key' => $i % 4, // kunci berotasi A, B, C, D
            ];
        }

        return array_slice($out, 0, self::JUMLAH_SOAL);
    }
}
