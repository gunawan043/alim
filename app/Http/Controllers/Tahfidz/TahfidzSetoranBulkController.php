<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzMutabaah;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzSurahMaster;
use App\Services\QuranPositionService;
use App\Services\TahfidzAccess;
use App\Services\TahfidzProgressService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Input setoran harian satu halaqah dalam satu layar.
 *
 * Semua santri aktif tampil sebagai baris. Guru memilih Ziyadah atau Rabt
 * per baris, mengisi materi dan nilai tikrar, lalu menyimpan sekali untuk
 * seluruh halaqah. Rabt memakai setoran_type 'robet' (data lama tetap utuh).
 */
class TahfidzSetoranBulkController extends Controller
{
    /** Setoran yang boleh dipilih per baris: kunci internal → label tampilan. */
    private const KEGIATAN = [
        'ziyadah' => 'Ziyadah',
        'robet' => 'Rabt',
    ];

    private const ROBET_STATUS = ['siap', 'belum_siap', 'perlu_diulang'];

    private const STATUS_SETORAN = ['lulus', 'ulang', 'ditunda'];

    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzProgressService $progress,
    ) {}

    public function index(Request $request, string $userId)
    {
        $groups = $this->accessibleGroups($request);

        if ($groups->isEmpty()) {
            return view('tahfidz.setoran.bulk', [
                'userId' => $userId,
                'groups' => $groups,
                'group' => null,
                'rows' => collect(),
                'mutabaahRows' => collect(),
                'tab' => 'setoran',
                'surahs' => collect(),
                'date' => null,
                'kegiatan' => self::KEGIATAN,
            ]);
        }

        $groupId = $request->input('group_id', $groups->first()->id);
        $group = $groups->firstWhere('id', $groupId) ?? $groups->first();
        $date = $this->safeDate($request->input('date'));
        // Satu halaman untuk setoran hafalan dan mutaba'ah; tab hanya penanda.
        $tab = $request->input('tab') === 'mutabaah' ? 'mutabaah' : 'setoran';

        return view('tahfidz.setoran.bulk', [
            'userId' => $userId,
            'groups' => $groups,
            'group' => $group,
            'rows' => $this->setoranRows($group, $date),
            'mutabaahRows' => $this->mutabaahRows($group, $date),
            'surahs' => TahfidzSurahMaster::orderBy('number')->get(['number', 'name_latin', 'name_arabic']),
            'date' => $date,
            'tab' => $tab,
            'kegiatan' => self::KEGIATAN,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function setoranRows(TahfidzGroup $group, string $date)
    {
        $members = $this->activeMembers($group);

        $studentIds = $members->pluck('student_id')->all();

        $existingToday = TahfidzSetoran::query()
            ->whereIn('student_id', $studentIds)
            ->where('tahfidz_group_id', $group->id)
            ->whereDate('setoran_date', $date)
            ->whereIn('setoran_type', array_keys(self::KEGIATAN))
            ->get()
            ->groupBy('student_id');

        $lastByStudent = $this->lastSetoranByStudent($studentIds, $group);

        return $members->map(function ($member) use ($existingToday, $lastByStudent) {
            $studentId = $member->student_id;
            $today = $existingToday->get($studentId, collect());
            $last = $lastByStudent->get($studentId);

            $lastPosition = $last ? [
                'juz' => $last->juz,
                'surah_start_name' => $last->surah_end_name ?: $last->surah_start_name,
                'ayat_start' => $last->ayat_end ? ((int) $last->ayat_end + 1) : $last->ayat_start,
                'halaman_start' => $last->halaman_end ? ((float) $last->halaman_end + 1) : $last->halaman_start,
            ] : null;

            return [
                'student' => $member->student,
                'ziyadah' => $today->firstWhere('setoran_type', 'ziyadah'),
                'rabt' => $today->firstWhere('setoran_type', 'robet'),
                'last' => $lastPosition,
            ];
        });
    }

    /**
     * Mutaba'ah harian per santri pada tanggal terpilih.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mutabaahRows(TahfidzGroup $group, string $date)
    {
        $members = $this->activeMembers($group);

        if ($members->isEmpty()) {
            return collect();
        }

        $records = TahfidzMutabaah::query()
            ->whereIn('student_id', $members->pluck('student_id')->all())
            ->whereDate('record_date', $date)
            ->get()
            ->keyBy('student_id');

        return $members->map(fn ($member) => [
            'student' => $member->student,
            'record' => $records->get($member->student_id),
        ]);
    }

    private function activeMembers(TahfidzGroup $group)
    {
        return TahfidzGroupMember::with('student:id,name,nis')
            ->where('tahfidz_group_id', $group->id)
            ->where('status', 'aktif')
            ->get()
            ->filter(fn ($member) => $member->student)
            ->sortBy(fn ($member) => $member->student->name)
            ->values();
    }

    public function store(Request $request, string $userId)
    {
        // Aturan per-field hanya untuk field yang nilainya diskalakan di sini.
        // Laravel membuang kunci yang tidak punya aturan, dan menamai ulang
        // kunci bernama 'rabt' sebagai entri array di validasi wildcard. Form
        // memakai nama wire 'robet' agar entri terkirim utuh.
        $validated = $request->validate([
            'tahfidz_group_id' => 'required|exists:tahfidz_groups,id',
            'setoran_date' => 'required|date',
            'entries' => 'required|array',
            'entries.*.student_id' => 'required|exists:students,id',
            'entries.*.ziyadah.hasil_hafalan' => 'nullable|numeric|min:0|max:100',
            'entries.*.ziyadah.juz' => 'nullable|integer|min:1|max:30',
            'entries.*.ziyadah.status' => 'nullable|in:lulus,ulang,ditunda',
        ]);

        $entries = $request->input('entries', []);

        $group = TahfidzGroup::findOrFail($validated['tahfidz_group_id']);
        abort_unless($this->access->canInputSetoran($request->user(), $group), 403, 'Anda tidak berwenang mencatat setoran halaqah ini.');

        $setoranDate = $validated['setoran_date'];
        $autoVerify = (bool) config('tahfidz.auto_verify_setoran', true);
        $saved = 0;
        $touchedStudents = [];

        DB::transaction(function () use ($entries, $group, $request, $setoranDate, $autoVerify, &$saved, &$touchedStudents) {
            foreach ($entries as $entry) {
                $studentId = $entry['student_id'];

                $isMember = TahfidzGroupMember::query()
                    ->where('tahfidz_group_id', $group->id)
                    ->where('student_id', $studentId)
                    ->where('status', 'aktif')
                    ->exists();

                if (! $isMember) {
                    abort(422, 'Santri bukan anggota aktif halaqah ini.');
                }

                foreach (self::KEGIATAN as $type => $__label) {
                    $row = $entry[$type] ?? null;

                    if (! is_array($row)) {
                        continue;
                    }

                    // Baris dianggap kosong bila materi hafalan belum diisi.
                    $materiKosong = blank($row['surah_start_name'] ?? null)
                        && blank($row['ayat_start'] ?? null)
                        && blank($row['juz'] ?? null)
                        && blank($row['halaman_start'] ?? null);

                    if ($materiKosong) {
                        continue;
                    }

                    $payload = $this->buildPayload($row, $type, $group, $setoranDate, $request);

                    $existing = TahfidzSetoran::query()
                        ->where('student_id', $studentId)
                        ->where('tahfidz_group_id', $group->id)
                        ->where('setoran_type', $type)
                        ->whereDate('setoran_date', $setoranDate)
                        ->first();

                    if ($existing) {
                        if ($this->isLocked($existing)) {
                            continue;
                        }
                        $existing->update($payload);
                        $setoran = $existing;
                    } else {
                        $setoran = TahfidzSetoran::create(array_merge($payload, [
                            'student_id' => $studentId,
                            'tahfidz_group_id' => $group->id,
                        ]));
                    }

                    if ($autoVerify) {
                        $this->progress->recalculateForSetoran($setoran);
                    }

                    $touchedStudents[$studentId] = true;
                    $saved++;
                }
            }
        });

        $message = $saved > 0
            ? $saved.' setoran tersimpan untuk '.count($touchedStudents).' santri.'
            : 'Tidak ada baris yang diisi, tidak ada setoran tersimpan.';

        return redirect()
            ->route('user.tahfidz.setoran-bulk.index', [
                'userId' => $userId,
                'group_id' => $group->id,
                'date' => $setoranDate,
            ])
            ->with($saved > 0 ? 'success' : 'error', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(array $row, string $type, TahfidzGroup $group, string $date, Request $request): array
    {
        $surahStart = $this->surahNumber($row['surah_start_name'] ?? null);
        $surahEnd = $this->surahNumber($row['surah_end_name'] ?? null);
        $ayatStart = $this->nullIfBlank($row['ayat_start'] ?? null);
        $ayatEnd = $this->nullIfBlank($row['ayat_end'] ?? null);

        $position = ($surahStart && $ayatStart)
            ? app(QuranPositionService::class)->range($surahStart, (int) $ayatStart, $surahEnd, $ayatEnd ? (int) $ayatEnd : null)
            : null;

        $pageStart = $position['page_start'] ?? $this->nullIfBlank($row['halaman_start'] ?? null);
        $pageEnd = $position['page_end'] ?? $pageStart;
        $lineStart = $position['line_start'] ?? $this->nullIfBlank($row['baris_start'] ?? null);
        $lineEnd = $position['line_end'] ?? $lineStart;

        $pages = ($pageStart !== null && $pageEnd !== null)
            ? max(0, (float) $pageEnd - (float) $pageStart + 1)
            : null;

        $nilaiHafalan = $this->nullIfBlank($row['hasil_hafalan'] ?? null);
        $nilaiTikrar = $this->nullIfBlank($row['nilai_tikrar'] ?? null);
        $nilaiRabt = $this->nullIfBlank($row['nilai_rabt'] ?? null);

        // Nilai tikrar disimpan di kolomnya sendiri dan dipakai di kedua jenis.
        $nilaiSetoran = $type === 'ziyadah' ? $nilaiHafalan : $nilaiRabt;

        $robetStatus = in_array($row['robet_status'] ?? null, self::ROBET_STATUS, true) ? $row['robet_status'] : null;
        $status = in_array($row['status'] ?? null, self::STATUS_SETORAN, true) ? $row['status'] : 'lulus';
        $catatan = $row['catatan_guru'] ?? null;

        return [
            'teacher_id' => $request->user()?->id,
            'academic_year_id' => $group->academic_year_id,
            'setoran_date' => $date,
            'setoran_type' => $type,
            'juz' => $this->nullIfBlank($row['juz'] ?? null),
            'surah_start_name' => $this->nullIfBlank($row['surah_start_name'] ?? null),
            'ayat_start' => $ayatStart,
            'surah_end_name' => $this->nullIfBlank($row['surah_end_name'] ?? null),
            'ayat_end' => $ayatEnd,
            'halaman_start' => $pageStart,
            'baris_start' => $lineStart,
            'halaman_end' => $pageEnd,
            'baris_end' => $lineEnd,
            'jumlah_halaman' => $pages,
            'hasil_hafalan' => $type === 'ziyadah' ? $nilaiHafalan : null,
            'nilai_setoran' => $nilaiSetoran,
            'nilai_tikrar' => $nilaiTikrar,
            'robet_status' => $type === 'robet' ? $robetStatus : null,
            'status' => $status,
            'catatan_guru' => is_string($catatan) ? mb_substr($catatan, 0, 3000) : null,
        ];
    }

    private function lastSetoranByStudent(array $studentIds, TahfidzGroup $group)
    {
        return TahfidzSetoran::query()
            ->whereIn('student_id', $studentIds)
            ->where('tahfidz_group_id', $group->id)
            ->where('academic_year_id', $group->academic_year_id)
            ->where('setoran_type', 'ziyadah')
            ->orderByDesc('setoran_date')
            ->orderByDesc('created_at')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');
    }

    private function accessibleGroups(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($request);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = TahfidzGroup::query()
            ->with(['school:id,name'])
            ->where('is_active', true)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($t) => $t->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function surahNumber(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $number = TahfidzSurahMaster::query()->where('name_latin', $name)->value('number');

        return $number ? (int) $number : null;
    }

    private function nullIfBlank(mixed $value): mixed
    {
        return $value === '' || $value === null ? null : $value;
    }

    private function isLocked(TahfidzSetoran $setoran): bool
    {
        return $setoran->verification_status === 'verified'
            && $setoran->verification_notes !== 'auto-verified';
    }

    private function safeDate(?string $value): string
    {
        try {
            return $value ? Carbon::parse($value)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
