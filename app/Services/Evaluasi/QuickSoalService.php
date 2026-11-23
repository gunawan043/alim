<?php

namespace App\Services\Evaluasi;

use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Jalur cepat soal: bank otomatis, simpan soal, opsi, hash, dan validasi
 * STS/SAS. Dipakai oleh Soal Saya, Cari Soal (clone), dan import Word.
 */
class QuickSoalService
{
    /**
     * Bank penyimpan pribadi milik Soal Saya.
     *
     * Soal wajib memiliki bank (`soal.bank_soal_id` NOT NULL), jadi soal
     * pribadi tetap disimpan di bank milik guru. Bank ini TIDAK tampil di
     * menu Bank Soal — pemisahan fungsi Soal Saya vs Bank Soal dilakukan
     * di sini, bukan dengan mengosongkan relasi.
     */
    public function bankFor(
        User $user,
        School $school,
        Subject $subject,
        ?GradeLevel $grade,
        ?AcademicYear $academicYear,
        string $semester
    ): BankSoal {
        $name = 'Soal Saya — '.$subject->name;
        $description = BankSoal::AUTO_BANK_MARKER.' Bank pribadi Soal Saya. Soal di sini dikelola di menu Soal Saya, bukan sebagai koleksi bersama.';

        $bank = BankSoal::firstOrCreate(
            [
                'school_id' => $school->id,
                'subject_id' => $subject->id,
                'academic_year_id' => $academicYear?->id,
                'semester' => $semester,
                'owner_user_id' => $user->id,
                'nama' => $name,
            ],
            [
                'fase' => $grade?->fase,
                'jenjang' => $grade?->level,
                'grade_level_id' => $grade?->id,
                'deskripsi' => $description,
                'jenis_soal' => 'campuran',
                'bahasa' => 'latin',
                'tingkat_kesulitan_target' => 'campuran',
                'is_public' => false,
                'shared_scope' => 'internal_school',
                'is_central' => false,
                'allow_cross_teacher_clone' => true,
                'total_soal' => 0,
                'created_by' => $user->id,
            ]
        );

        // Bank lama buatan versi sebelumnya belum bertanda; tandai sekalian
        // agar tidak lagi muncul ganda di menu Bank Soal.
        if (! $bank->isAutoBank()) {
            $bank->forceFill(['deskripsi' => $description])->save();
        }

        return $bank;
    }

    /**
     * @param  array<int, array{label?: string, teks_opsi?: string, is_correct?: bool|int|string}>  $options
     * @return array{soal: Soal, warning: ?string}
     */
    public function create(
        User $user,
        School $school,
        Subject $subject,
        ?GradeLevel $grade,
        ?AcademicYear $academicYear,
        string $semester,
        array $data,
        array $options = [],
        ?string $correctLabel = null
    ): array {
        $bank = $this->bankFor($user, $school, $subject, $grade, $academicYear, $semester);
        $engine = app(ContentHashEngine::class);
        $tipe = $data['tipe_soal'] ?? 'pg';
        $correctTexts = collect($options)
            ->filter(fn ($option) => $this->isCorrectOption($option, $correctLabel))
            ->pluck('teks_opsi')
            ->filter()
            ->values()
            ->all();

        $soal = null;
        $warning = null;

        DB::transaction(function () use (&$soal, $user, $bank, $data, $tipe, $options, $correctLabel, $engine, $correctTexts) {
            $soal = new Soal;
            $soal->fill([
                'bank_soal_id' => $bank->id,
                'tp_id' => $data['tp_id'] ?? null,
                'wacana_id' => $data['wacana_id'] ?? null,
                'derived_from_soal_id' => $data['derived_from_soal_id'] ?? null,
                'materi' => $data['materi'] ?? null,
                'tipe_soal' => $tipe,
                'bahasa' => $data['bahasa'] ?? 'latin',
                'pertanyaan' => $data['pertanyaan'],
                'pembahasan' => $data['pembahasan'] ?? null,
                'bobot_default' => $data['bobot_default'] ?? 1,
                'tingkat_kesulitan_estimasi' => $data['tingkat_kesulitan_estimasi'] ?? 'sedang',
                'waktu_estimasi_menit' => $data['waktu_estimasi_menit'] ?? 2,
                'tags' => ! empty($data['tags'])
                    ? array_values(array_filter(array_map('trim', explode(',', (string) $data['tags']))))
                    : null,
                'status' => 'draft',
                'workflow_status' => Soal::WORKFLOW_DRAFT,
                'jenis_asesmen' => $data['jenis_asesmen'] ?? Soal::JENIS_ASESMEN_HARIAN,
                'dibuat_oleh' => $user->id,
            ]);

            $hash = $engine->hashFromSoal($soal->pertanyaan, $correctTexts);
            if (Soal::withTrashed()->where('content_hash', $hash)->exists()) {
                $hash = hash('sha256', $hash.'|'.Str::uuid());
            }
            $soal->content_hash = $hash;
            $soal->shingles_hash = $engine->shinglesFromSoal($soal->pertanyaan);
            $soal->save();

            if (in_array($tipe, ['pg', 'bs', 'jodoh'], true)) {
                foreach (array_values($options) as $index => $option) {
                    SoalOption::create([
                        'soal_id' => $soal->id,
                        'label' => strtoupper((string) ($option['label'] ?? chr(65 + $index))),
                        'teks_opsi' => (string) ($option['teks_opsi'] ?? ''),
                        'is_correct' => $this->isCorrectOption($option, $correctLabel),
                        'urutan' => $index + 1,
                    ]);
                }
            }

            $bank->forceFill(['total_soal' => $bank->soal()->count()])->save();
        });

        $warning = $this->submitForValidation($soal->fresh(), $user);

        return ['soal' => $soal->fresh(), 'warning' => $warning];
    }

    /**
     * Salin soal yang ditemukan di Cari Soal ke Soal Saya.
     *
     * @return array{soal: Soal, warning: ?string}
     */
    public function clone(User $user, School $school, Soal $source): array
    {
        $source->loadMissing(['bankSoal.subject', 'bankSoal.gradeLevel', 'bankSoal.academicYear', 'options']);

        $subject = $source->bankSoal?->subject;
        if (! $subject) {
            throw new \RuntimeException('Soal sumber tidak memiliki mata pelajaran.');
        }

        $grade = $source->bankSoal?->gradeLevel;
        $academicYear = $source->bankSoal?->academicYear;
        $semester = $source->bankSoal?->semester ?: 'ganjil';

        return $this->create(
            $user,
            $school,
            $subject,
            $grade,
            $academicYear,
            $semester,
            [
                'tp_id' => $source->tp_id,
                'materi' => $source->materi,
                'tipe_soal' => $source->tipe_soal,
                'bahasa' => $source->bahasa,
                'pertanyaan' => $source->pertanyaan,
                'pembahasan' => $source->pembahasan,
                'bobot_default' => $source->bobot_default,
                'tingkat_kesulitan_estimasi' => $source->tingkat_kesulitan_estimasi,
                'waktu_estimasi_menit' => $source->waktu_estimasi_menit,
                'tags' => is_array($source->tags) ? implode(',', $source->tags) : $source->tags,
                'jenis_asesmen' => $source->jenis_asesmen ?: Soal::JENIS_ASESMEN_HARIAN,
                'derived_from_soal_id' => $source->id,
            ],
            $source->options->map(fn ($option) => [
                'label' => $option->label,
                'teks_opsi' => $option->teks_opsi,
                'is_correct' => $option->is_correct,
            ])->values()->all()
        );
    }

    /**
     * Ajukan soal STS/SAS ke review guru serumpun.
     * Soal harian/formatif tidak dikirim ke review.
     */
    public function submitForValidation(Soal $soal, User $user): ?string
    {
        if (! $soal->requiresValidation()) {
            return null;
        }

        try {
            app(ReviewWorkflowService::class)->submit($soal, $user);

            return null;
        } catch (ValidationException $e) {
            return $e->validator->errors()->first('reviewer')
                ?: 'Soal belum dapat diajukan ke review guru serumpun.';
        } catch (\Throwable $e) {
            return 'Soal tersimpan, tetapi gagal diajukan ke review: '.$e->getMessage();
        }
    }

    private function isCorrectOption(array $option, ?string $correctLabel): bool
    {
        if ($correctLabel !== null && $correctLabel !== '') {
            return strtoupper((string) ($option['label'] ?? '')) === strtoupper(trim($correctLabel));
        }

        return filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
