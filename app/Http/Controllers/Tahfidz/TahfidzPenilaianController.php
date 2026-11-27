<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGradebook;
use App\Models\TahfidzGroup;
use App\Services\TahfidzAccess;
use App\Services\TahfidzGradebookCalculator;
use App\Services\TahfidzGradebookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Buku nilai tahfidz per halaqah.
 *
 * Nilai mapel tahfidz TIDAK diambil dari rata-rata setoran dan TIDAK ditulis
 * ke buku mapel umum (`admin_nilai_sumatif`). Nilainya diisi guru di sini
 * dengan empat kolom: Ziyadah, Rabt, STS, dan SAS — sesuai format penilaian
 * yang berlaku untuk tahfidz. Bobot tiap kolom diatur guru sendiri, sama
 * seperti buku nilai mapel lain.
 *
 * Buku diikat ke halaqah (bukan rombel) karena anggota satu halaqah bisa
 * berasal dari beberapa rombel dalam satu tingkat. Saat masuk rapor, nilai
 * dipetakan ke rombel masing-masing santri.
 *
 * Satu halaqah = satu buku, bukan satu (mapel x rombel). Mapel tahfidz hanya
 * satu, jadi mapel tidak perlu dipilih guru; bila data lama memuat lebih dari
 * satu mapel tahfidz, tab mapel ditampilkan seperti buku nilai sumatif.
 */
class TahfidzPenilaianController extends Controller
{
    /** Kolom nilai pada format tahfidz — sama dengan leger. */
    private const COLUMNS = ['npt', 'ziyadah', 'rabt', 'tikrar', 'sts', 'sas'];

    private const COLUMN_LABELS = [
        'npt' => 'NPT',
        'ziyadah' => 'Ziyadah',
        'rabt' => 'Rabt',
        'tikrar' => 'Tikrar',
        'sts' => 'STS',
        'sas' => 'SAS',
    ];

    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzGradebookService $books,
        private readonly TahfidzGradebookCalculator $calculator,
    ) {}

    public function index(Request $request, string $userId)
    {
        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();

        $semester = in_array($request->input('semester'), ['ganjil', 'genap'], true)
            ? $request->input('semester')
            : ($academicYear?->semester ?: 'ganjil');

        // Filter utama: halaqah. Tanpa group_id, daftar tetap tampil agar guru
        // bisa memilih — tetapi tabel nilai baru dimuat setelah halaqah dipilih.
        $groups = $academicYear
            ? $this->accessibleGroups($request, $academicYear)
            : collect();

        $groupId = $request->input('group_id');
        $group = $groupId ? $groups->firstWhere('id', $groupId) : null;

        $books = collect();
        $book = null;
        $rows = collect();
        $members = collect();

        if ($group) {
            // Buku dibuat otomatis saat dibuka (satu per halaqah per mapel
            // tahfidz), sama seperti daftar Buku Nilai Saya. Tanpa ini guru
            // harus membuat buku manual padahal konteksnya sudah jelas.
            $this->books->ensureBooks($group, $academicYear->id);
            $books = $this->booksForGroup($group, $academicYear, $semester);

            if ($books->isNotEmpty()) {
                $subjectId = $request->input('subject_id', $books->first()->subject_id);
                $book = $books->firstWhere('subject_id', $subjectId) ?? $books->first();

                $members = $this->books->activeMembers($group, $academicYear->id);
                $scores = $this->books->existingScores($book);

                $rows = $members->map(fn ($member) => [
                    'member' => $member,
                    'score' => $scores->get($member['student_id']),
                ]);
            }
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(function ($row) use ($needle) {
                $name = mb_strtolower((string) ($row['member']['name'] ?? ''));
                $nis = mb_strtolower((string) ($row['member']['nis'] ?? ''));

                return str_contains($name, $needle) || str_contains($nis, $needle);
            })->values();
        }

        return view('tahfidz.penilaian.index', [
            'userId' => $userId,
            'groups' => $groups,
            'group' => $group,
            'books' => $books,
            'book' => $book,
            'rows' => $rows,
            'members' => $members,
            'semester' => $semester,
            'academicYear' => $academicYear,
            'search' => $search,
            'columns' => self::COLUMNS,
            'columnLabels' => self::COLUMN_LABELS,
            'surahs' => \App\Models\TahfidzSurahMaster::orderBy('number')->get(['number', 'name_latin', 'name_arabic']),
            'weightErrors' => [],
        ]);
    }

    /**
     * Simpan nilai. Mendukung dua cara pakai:
     *  - formulir penuh (tombol Simpan / Enter) — redirect dengan pesan;
     *  - autosave AJAX per setoran (seperti buku nilai sumatif) — JSON.
     */
    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'tahfidz_group_id' => 'required|exists:tahfidz_groups,id',
            'tahfidz_gradebook_id' => 'required|exists:tahfidz_gradebooks,id',
            'semester' => 'required|in:ganjil,genap',
            'scores' => 'array',
            'scores.*.ziyadah' => 'nullable|numeric|min:0|max:100',
            'scores.*.rabt' => 'nullable|numeric|min:0|max:100',
            'scores.*.sts' => 'nullable|numeric|min:0|max:100',
            'scores.*.sas' => 'nullable|numeric|min:0|max:100',
            'scores.*.ket' => 'nullable|string|max:100',
            'materi.sts.surah_start' => 'nullable|string|max:100',
            'materi.sts.ayah_start' => 'nullable|integer|min:1',
            'materi.sts.surah_end' => 'nullable|string|max:100',
            'materi.sts.ayah_end' => 'nullable|integer|min:1',
            'materi.sts.juz' => 'nullable|integer|min:1|max:30',
            'materi.sts.page_start' => 'nullable|integer|min:1|max:604',
            'materi.sts.line_start' => 'nullable|integer|min:1|max:15',
            'materi.sts.page_end' => 'nullable|integer|min:1|max:604',
            'materi.sts.line_end' => 'nullable|integer|min:1|max:15',
            'materi.sas.surah_start' => 'nullable|string|max:100',
            'materi.sas.ayah_start' => 'nullable|integer|min:1',
            'materi.sas.surah_end' => 'nullable|string|max:100',
            'materi.sas.ayah_end' => 'nullable|integer|min:1',
            'materi.sas.juz' => 'nullable|integer|min:1|max:30',
            'materi.sas.page_start' => 'nullable|integer|min:1|max:604',
            'materi.sas.line_start' => 'nullable|integer|min:1|max:15',
            'materi.sas.page_end' => 'nullable|integer|min:1|max:604',
            'materi.sas.line_end' => 'nullable|integer|min:1|max:15',
        ]);

        $group = TahfidzGroup::findOrFail($validated['tahfidz_group_id']);
        abort_unless($this->access->canInputSetoran($request->user(), $group), 403, 'Anda tidak berwenang mengisi nilai halaqah ini.');

        $book = TahfidzGradebook::findOrFail($validated['tahfidz_gradebook_id']);
        abort_unless(
            $book->tahfidz_group_id === $group->id && $book->semester === $validated['semester'],
            422,
            'Buku nilai tidak sesuai halaqah atau semester yang dipilih.',
        );

        $academicYear = AcademicYear::where('is_active', true)->first();
        abort_unless($academicYear, 422, 'Belum ada tahun ajaran aktif.');

        // Materi ujian disimpan ke buku (per halaqah), bukan per santri.
        if (is_array($request->input('materi'))) {
            $this->saveExamMaterials($book, $request->input('materi'));
        }

        $members = $this->books->activeMembers($group, $academicYear->id);

        // Nilai ditulis dulu, lalu bobot (bila dikirim) diverifikasi. Urutan ini
        // membuat pesan kesalahan bobot tidak membuang nilai yang sudah diisi.
        $saved = $this->books->saveScores($book, $validated['scores'] ?? [], $members);

        $redirect = redirect()
            ->route('user.tahfidz.penilaian.index', [
                'userId' => $userId,
                'group_id' => $group->id,
                'subject_id' => $book->subject_id,
                'semester' => $validated['semester'],
            ]);

        $wantsJson = $request->ajax() || $request->wantsJson();

        if (! $request->has('weights')) {
            if ($wantsJson) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nilai tersimpan untuk '.$saved.' santri.',
                    'saved_rows' => $this->savedRows($book, array_keys($validated['scores'] ?? [])),
                ]);
            }

            return $redirect->with('success', 'Nilai tersimpan untuk '.$saved.' santri.');
        }

        $weights = [];
        foreach (TahfidzGradebookCalculator::KEYS as $key) {
            $weights[$key] = $request->input('weights.'.$key);
        }

        $errors = $this->calculator->validateWeights($weights);

        if ($errors !== []) {
            $message = 'Nilai tersimpan, tetapi bobot tidak diubah: '.implode(' ', $errors);

            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'saved_rows' => $this->savedRows($book, array_keys($validated['scores'] ?? [])),
                ], 422);
            }

            return $redirect->with('error', $message);
        }

        DB::transaction(function () use ($book, $weights) {
            $attributes = [];
            foreach (TahfidzGradebookCalculator::KEYS as $key) {
                $attributes['weight_'.$key] = (float) $weights[$key];
            }
            $book->update($attributes);

            // Bobot baru harus langsung berlaku untuk nilai yang sudah ada,
            // agar rapor memakai angka terbaru tanpa menunggu input ulang.
            $book->refresh()->recalcAll();
        });

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'message' => 'Nilai dan bobot tersimpan.',
                'saved_rows' => $this->savedRows($book->refresh(), array_keys($validated['scores'] ?? [])),
            ]);
        }

        return $redirect->with('success', 'Nilai dan bobot tersimpan. NR Final dihitung ulang untuk seluruh santri.');
    }

    /**
     * Simpan materi ujian STS/SAS pada buku.
     *
     * Halaman dan baris diambil dari dataset Mushaf Madinah bila surah dan
     * ayat sudah dipilih, sehingga guru tidak perlu mengisinya sendiri. Isian
     * manual tetap dipakai bila guru mengubahnya.
     *
     * @param  array<string, array<string, mixed>>  $materi
     */
    private function saveExamMaterials(TahfidzGradebook $book, array $materi): void
    {
        $positions = app(\App\Services\QuranPositionService::class);
        $updates = [];

        foreach (['sts' => 'STS', 'sas' => 'SAS'] as $jenis => $label) {
            $row = $materi[$jenis] ?? null;
            if (! is_array($row)) {
                continue;
            }

            $surahStart = $this->nullIfBlank($row['surah_start'] ?? null);
            $surahEnd = $this->nullIfBlank($row['surah_end'] ?? null) ?: $surahStart;
            $ayahStart = $this->nullIfBlank($row['ayah_start'] ?? null);
            $ayahEnd = $this->nullIfBlank($row['ayah_end'] ?? null) ?: $ayahStart;

            $pageStart = $this->nullIfBlank($row['page_start'] ?? null);
            $lineStart = $this->nullIfBlank($row['line_start'] ?? null);
            $pageEnd = $this->nullIfBlank($row['page_end'] ?? null);
            $lineEnd = $this->nullIfBlank($row['line_end'] ?? null);

            // Lengkapi posisi yang belum diisi dari dataset Mushaf.
            $nomorAwal = $this->surahNumber($surahStart);
            if (($pageStart === null || $lineStart === null) && $nomorAwal && $ayahStart) {
                $position = $positions->range(
                    $nomorAwal,
                    (int) $ayahStart,
                    $this->surahNumber($surahEnd) ?: $nomorAwal,
                    $ayahEnd ? (int) $ayahEnd : null,
                );

                if ($position) {
                    $pageStart ??= $position['page_start'];
                    $lineStart ??= $position['line_start'];
                    $pageEnd ??= $position['page_end'];
                    $lineEnd ??= $position['line_end'];
                }
            }

            $updates[$jenis.'_surah_start'] = $surahStart;
            $updates[$jenis.'_surah_end'] = $surahEnd;
            $updates[$jenis.'_ayah_start'] = $ayahStart;
            $updates[$jenis.'_ayah_end'] = $ayahEnd;
            $updates[$jenis.'_juz'] = $this->nullIfBlank($row['juz'] ?? null);
            $updates[$jenis.'_page_start'] = $pageStart;
            $updates[$jenis.'_line_start'] = $lineStart;
            $updates[$jenis.'_page_end'] = $pageEnd;
            $updates[$jenis.'_line_end'] = $lineEnd;
            $updates[$jenis.'_material'] = $this->materialLabel(
                $surahStart, $ayahStart, $surahEnd, $ayahEnd, $updates[$jenis.'_juz'] ?? null,
            );
        }

        if ($updates !== []) {
            $book->update($updates);
        }
    }

    /**
     * Ringkasan materi untuk ditampilkan, mis. "Juz 30 · An-Naba 1 – An-Nas 6".
     */
    private function materialLabel(?string $surahStart, $ayahStart, ?string $surahEnd, $ayahEnd, $juz): ?string
    {
        if (! $surahStart) {
            return null;
        }

        $range = $surahStart.($ayahStart ? ':'.$ayahStart : '');
        if ($surahEnd && $surahEnd !== $surahStart) {
            $range .= ' – '.$surahEnd.($ayahEnd ? ':'.$ayahEnd : '');
        } elseif ($ayahEnd && $ayahEnd != $ayahStart) {
            $range .= '–'.$ayahEnd;
        }

        return trim(($juz ? 'Juz '.$juz.' · ' : '').$range);
    }

    private function surahNumber(?string $name): ?int
    {
        if (blank($name)) {
            return null;
        }

        return \App\Models\TahfidzSurahMaster::where('name_latin', $name)->value('number');
    }

    private function nullIfBlank($value): mixed
    {
        return ($value === null || $value === '') ? null : $value;
    }

    /**
     * Baris nilai terkini untuk balasan autosave (pembaruan sel tanpa reload).
     *
     * @param  array<int, string>  $studentIds
     * @return array<int, array<string, mixed>>
     */
    private function savedRows(TahfidzGradebook $book, array $studentIds): array
    {
        $weights = $book->weights();

        return $book->scores()
            ->whereIn('student_id', $studentIds)
            ->get()
            ->map(fn ($score) => [
                'student_id' => (string) $score->student_id,
                'npt' => $score->npt,
                'ziyadah' => $score->ziyadah,
                'rabt' => $score->rabt,
                'tikrar' => $score->tikrar,
                'sts' => $score->sts,
                'sas' => $score->sas,
                'nr_final' => $score->nr_final,
                'weighted' => $score->nr_final !== null
                    ? round((float) $score->nr_final * $book->totalWeight() / 100, 2)
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Buku nilai halaqah pada periode ini, satu per mapel tahfidz.
     */
    private function booksForGroup(TahfidzGroup $group, AcademicYear $academicYear, string $semester)
    {
        return TahfidzGradebook::with('subject')
            ->where('tahfidz_group_id', $group->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($book) => $book->subject?->name)
            ->values();
    }

    private function accessibleGroups(Request $request, AcademicYear $academicYear)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($request);

        $query = TahfidzGroup::query()
            ->with(['school:id,name', 'studyGroup:id,name'])
            ->where('is_active', true)
            ->where('academic_year_id', $academicYear->id);

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                // Tanpa school_id (kepegawaian belum diisi), filter ini akan
                // mengosongkan hasil padahal guru tetap berhak melihat
                // halaqahnya sendiri. Jatuh ke lingkup pribadi.
                if ($schoolId) {
                    $query->where('school_id', $schoolId);
                } else {
                    $this->scopeToOwnHalakah($query, $user);
                }
            } else {
                $this->scopeToOwnHalakah($query, $user);
            }
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Halaqah tempat guru ini mengajar — sumbernya sama dengan daftar buku
     * nilai (teacher_id halaqah atau tabel pengajar halaqah).
     */
    private function scopeToOwnHalakah($query, $user): void
    {
        $query->where(function ($q) use ($user) {
            $q->where('teacher_id', $user->id)
                ->orWhere('coordinator_id', $user->id)
                ->orWhereHas('teachers', fn ($t) => $t->where('user_id', $user->id)->where('is_active', true));
        });
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
