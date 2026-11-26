<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\InstitutionDecree;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Menentukan periode (semester) yang berlaku berdasarkan SK aktif sekolah.
 *
 * Semester TIDAK dibaca dari academic_years.semester karena kolom itu hanya
 * menyimpan satu nilai untuk sepasang tahun ajaran, sedangkan satu tahun
 * ajaran memuat dua semester. Yang mengikat adalah masa berlaku SK pembagian
 * tugas: SK ganjil berakhir di Desember, SK genap berakhir di Juni.
 */
class DecreePeriodService
{
    public const GANJIL = 'ganjil';

    public const GENAP = 'genap';

    /** SK dianggap kedaluwarsa bila masa berlakunya sudah lewat. */
    public const STATE_EXPIRED = 'expired';

    public const STATE_ACTIVE = 'active';

    public const STATE_MISSING = 'missing';

    /**
     * Semester berdasarkan tanggal: Jan–Jun = genap, Jul–Des = ganjil.
     *
     * Dipakai sebagai penentu terakhir ketika SK tidak mencantumkan batas
     * yang jelas (mis. end_date kosong).
     */
    public static function semesterOfDate(CarbonInterface $date): string
    {
        return $date->month <= 6 ? self::GENAP : self::GANJIL;
    }

    /**
     * SK pembagian tugas yang berlaku untuk sebuah sekolah.
     *
     * Urutan pilihan: SK aktif yang mencakup hari ini → SK aktif terbaru
     * (walau sudah lewat, dipakai dengan peringatan) → SK aktif terbaru.
     */
    public function activeDecree(?string $schoolId, ?CarbonInterface $at = null): ?InstitutionDecree
    {
        if (! $schoolId) {
            return null;
        }

        $at ??= Carbon::today();

        $decrees = InstitutionDecree::query()
            ->where('school_id', $schoolId)
            ->where('status', InstitutionDecree::STATUS_ACTIVE)
            ->orderByDesc('effective_date')
            ->get();

        if ($decrees->isEmpty()) {
            return null;
        }

        $covering = $decrees->first(fn (InstitutionDecree $d) => $this->covers($d, $at));

        return $covering ?? $decrees->first();
    }

    /**
     * Semester yang berlaku menurut SK pada sebuah sekolah.
     *
     * @return array{
     *     semester: string,
     *     source: string,
     *     state: string,
     *     decree: ?InstitutionDecree,
     *     message: ?string
     * }
     */
    public function resolve(?string $schoolId, ?string $academicYearId = null, ?CarbonInterface $at = null): array
    {
        $at ??= Carbon::today();
        $decree = $this->activeDecree($schoolId, $at);

        if (! $decree) {
            return [
                'semester' => $this->fallbackSemester($academicYearId, $at),
                'source' => 'academic_year',
                'state' => self::STATE_MISSING,
                'decree' => null,
                'message' => 'Sekolah belum punya SK pembagian tugas yang aktif. Semester mengikuti pengaturan tahun ajaran.',
            ];
        }

        $state = $this->covers($decree, $at) ? self::STATE_ACTIVE : self::STATE_EXPIRED;
        $semester = $this->semesterFromDecree($decree);

        $message = $state === self::STATE_EXPIRED
            ? sprintf(
                'SK "%s" sudah berakhir pada %s. Data nilai masih mengikuti SK ini; perbarui SK agar semester berikutnya benar.',
                $decree->decree_number,
                $decree->end_date?->translatedFormat('d F Y') ?? '-'
            )
            : null;

        return [
            'semester' => $semester,
            'source' => 'decree',
            'state' => $state,
            'decree' => $decree,
            'message' => $message,
        ];
    }

    /**
     * Semester menurut SK.
     *
     * Urutan keandalan: end_date (batas akhir semester) → semester yang
     * tertulis pada judul → tanggal berlaku/ditetapkan. Tanggal berlaku
     * sengaja menjadi pilihan TERAKHIR: banyak SK tanpa end_date yang
     * dimulai Juli tetapi berlaku untuk semester genap, sehingga tanggal
     * awal menyesatkan sementara judulnya eksplisit.
     */
    public function semesterFromDecree(InstitutionDecree $decree): string
    {
        if ($decree->end_date) {
            return self::semesterOfDate($decree->end_date);
        }

        $fromTitle = $this->semesterFromTitle((string) $decree->title);

        if ($fromTitle) {
            return $fromTitle;
        }

        return self::semesterOfDate($decree->effective_date ?? $decree->issued_date ?? Carbon::today());
    }

    /**
     * Baca semester dari judul SK ("... Semester Genap ...").
     */
    public function semesterFromTitle(string $title): ?string
    {
        if (preg_match('/\bgenap\b/i', $title)) {
            return self::GENAP;
        }

        if (preg_match('/\bganjil\b/i', $title)) {
            return self::GANJIL;
        }

        return null;
    }

    /**
     * Apakah SK berlaku pada tanggal tertentu.
     */
    public function covers(InstitutionDecree $decree, ?CarbonInterface $at = null): bool
    {
        $at ??= Carbon::today();

        if ($decree->effective_date && $at->lt($decree->effective_date->copy()->startOfDay())) {
            return false;
        }

        // end_date inklusif: SK berlaku sampai akhir hari tersebut.
        if ($decree->end_date && $at->gt($decree->end_date->copy()->endOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * Periode efektif SK untuk ditampilkan (mis. "13 Jul 2026 – 31 Des 2026").
     */
    public function periodLabel(InstitutionDecree $decree): string
    {
        $start = $decree->effective_date?->translatedFormat('d M Y') ?? '-';
        $end = $decree->end_date?->translatedFormat('d M Y') ?? 'tanpa batas';

        return $start.' – '.$end;
    }

    /**
     * Cadangan: semester dari tahun ajaran (perilaku lama) atau tanggal.
     */
    private function fallbackSemester(?string $academicYearId, CarbonInterface $at): string
    {
        $semester = $academicYearId
            ? AcademicYear::whereKey($academicYearId)->value('semester')
            : null;

        if (in_array($semester, [self::GANJIL, self::GENAP], true)) {
            return $semester;
        }

        return self::semesterOfDate($at);
    }
}
