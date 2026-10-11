<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Menu Super Admin — Struktur Kurikulum:
 * ringkasan struktur per satuan pendidikan + terapkan ulang seeder wajib.
 * Mapel pemadatan dikelola masing-masing satuan pendidikan.
 */
class CurriculumStructureController extends Controller
{
    private const JENJANG_DEFS = [
        'SD' => ['levels' => [1, 2, 3, 4, 5, 6], 'labels' => ['I', 'II', 'III', 'IV', 'V', 'VI']],
        'SMP' => ['levels' => [7, 8, 9], 'labels' => ['VII', 'VIII', 'IX']],
        'SMA' => ['levels' => [10, 11, 11, 12, 12], 'labels' => ['X', 'XI (BHS)', 'XI (IPA)', 'XII (BHS)', 'XII (IPA)'], 'tracks' => [null, 'bahasa', 'ipa', 'bahasa', 'ipa']],
    ];


    public function index(Request $request)
    {
        $this->authorizeSuper($request);

        $schools = School::query()->orderBy('name')->get()->map(function (School $school) {
            $gradeIds = GradeLevel::where('school_id', $school->id)->pluck('id');

            return [
                'school' => $school,
                'levels' => $gradeIds->count(),
                'pondok' => Subject::where('school_id', $school->id)->where('source', 'pondok')->count(),
                'pemadatan' => Subject::where('school_id', $school->id)->where('source', 'pemadatan')->count(),
                'allocations' => GradeLevelSubject::whereIn('grade_level_id', $gradeIds)->count(),
            ];
        });

        return view('super-admin.curriculum-structure.index', [
            'userId' => $request->route('userId') ?? auth()->id(),
            'schools' => $schools,
        ]);
    }

    /**
     * Matriks struktur kurikulum PER JENJANG (format tabel pondok):
     * Mata Pelajaran × Alokasi Waktu Per Pekan (kolom per tingkat).
     */
    public function matrix(Request $request)
    {
        $this->authorizeSuper($request);

        $jenjangDefs = self::JENJANG_DEFS;

        // Filter per jenjang (Semua / SD / SMP / SMA).
        $filter = strtoupper((string) $request->query('jenjang', ''));
        if (array_key_exists($filter, $jenjangDefs)) {
            $jenjangDefs = [$filter => $jenjangDefs[$filter]];
        }

        $jenjangs = [];

        foreach ($jenjangDefs as $nama => $def) {
            $schoolIds = School::whereIn('school_level', array_map('strtolower', [strtolower($nama), $nama === 'SMA' ? 'ma' : $nama]))->pluck('id');
            $schoolIds = School::where('school_level', strtolower($nama))->pluck('id');

            if ($schoolIds->isEmpty()) { continue; }

            $gradesByLevel = GradeLevel::whereIn('school_id', $schoolIds)->get()->groupBy('level');

            $subjects = Subject::with('subjectGroup:id,code')
                ->whereIn('school_id', $schoolIds)
                ->where('source', 'pondok')
                ->orderBy('name')
                ->get()
                ->unique('code')
                ->values();

            $rows = [];

            foreach ($subjects as $subject) {
                $subjectIds = Subject::whereIn('school_id', $schoolIds)->where('code', $subject->code)->pluck('id');
                $cells = [];

                foreach ($def['levels'] as $i => $level) {
                    $gradeIds = ($gradesByLevel[$level] ?? collect())->pluck('id');
                    $track = $def['tracks'][$i] ?? null;

                    if ($gradeIds->isEmpty()) { $cells[] = null; continue; }

                    $query = \App\Models\GradeLevelSubject::whereIn('grade_level_id', $gradeIds)
                        ->whereIn('subject_id', $subjectIds);

                    if ($track === null) {
                        $value = (clone $query)->whereNull('track')->max('allocation_hours');
                    } else {
                        $value = (clone $query)->where('track', $track)->max('allocation_hours')
                            ?? (clone $query)->whereNull('track')->max('allocation_hours');
                    }

                    $cells[] = $value !== null ? (int) $value : null;
                }

                $rows[] = [
                    'code' => $subject->code,
                    'name' => $subject->name,
                    'group_id' => $subject->subject_group_id,
                    'group' => $subject->subjectGroup?->code,
                    'cells' => $cells,
                ];
            }

            $totals = [];
            foreach (array_keys($def['labels']) as $col) {
                $totals[$col] = collect($rows)->sum(fn ($r) => $r['cells'][$col] ?? 0);
            }

            $pemadatan = Subject::whereIn('school_id', $schoolIds)->where('source', 'pemadatan')->count();

            $columns = collect($def['labels'])->map(function ($label, $i) use ($def) {
                $level = $def['levels'][$i] ?? null;
                $track = $def['tracks'][$i] ?? null;

                return ['label' => $label, 'key' => $track ? $level.':'.$track : (string) $level];
            })->values()->all();

            $jenjangs[] = [
                'nama' => $nama,
                'labels' => $def['labels'],
                'levels' => $def['levels'],
                'tracks' => $def['tracks'] ?? [],
                'columns' => $columns,
                'rows' => $rows,
                'totals' => $totals,
                'pemadatan' => $pemadatan,
            ];
        }

        $jenjangColumns = collect($jenjangs)->mapWithKeys(fn ($j) => [$j['nama'] => $j['columns']])->all();

        return view('super-admin.curriculum-structure.matrix', [
            'userId' => $request->route('userId') ?? auth()->id(),
            'jenjangs' => collect($jenjangs),
            'jenjangColumns' => $jenjangColumns,
            'filter' => $filter,
            'subjectGroups' => \App\Models\SubjectGroup::orderBy('sort_order')->get(['id', 'name', 'code']),
        ]);
    }

    public function apply(Request $request)
    {
        $this->authorizeSuper($request);

        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\CurriculumStructureSeeder::class,
            '--force' => true,
        ]);

        return back()->with('success', 'Struktur kurikulum diterapkan ke seluruh satuan pendidikan (idempoten — data pemadatan tidak tersentuh).');
    }

    /**
     * Simpan (tambah/perbarui) mapel pondok pada suatu jenjang —
     * diterapkan ke SEMUA satuan pendidikan pada jenjang tersebut.
     */
    public function storeSubject(Request $request)
    {
        $this->authorizeSuper($request);
        $this->saveSubject($request, null);

        return back()->with('success', 'Mapel struktur kurikulum berhasil disimpan ke seluruh satuan pendidikan jenjang tersebut.');
    }

    public function updateSubject(Request $request, string $code)
    {
        $this->authorizeSuper($request);
        $this->saveSubject($request, $code);

        return back()->with('success', 'Mapel struktur kurikulum diperbarui.');
    }

    public function destroySubject(Request $request, string $code)
    {
        $this->authorizeSuper($request);

        $jenjang = strtoupper((string) $request->input('jenjang'));
        $schoolIds = School::where('school_level', strtolower($jenjang))->pluck('id');
        $subjectIds = Subject::whereIn('school_id', $schoolIds)->where('code', $code)->pluck('id');

        GradeLevelSubject::whereIn('subject_id', $subjectIds)->delete();
        Subject::whereIn('id', $subjectIds)->delete();

        return back()->with('success', 'Mapel dihapus dari struktur jenjang '.$jenjang.'.');
    }

    private function saveSubject(Request $request, ?string $editingCode): void
    {
        $data = $request->validate([
            'jenjang' => 'required|in:SD,SMP,SMA',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'subject_group_id' => 'nullable|exists:subject_groups,id',
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|integer|min:0|max:40',
        ]);

        $jenjang = $data['jenjang'];
        $def = self::JENJANG_DEFS[$jenjang];
        $schoolIds = School::where('school_level', strtolower($jenjang))->pluck('id');

        if ($schoolIds->isEmpty()) {
            abort(422, 'Belum ada satuan pendidikan pada jenjang '.$jenjang.'.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $def, $schoolIds, $editingCode) {
            foreach ($schoolIds as $schoolId) {
                $subject = Subject::updateOrCreate(
                    ['school_id' => $schoolId, 'code' => strtoupper($data['code'])],
                    [
                        'name' => $data['name'],
                        'subject_group_id' => $data['subject_group_id'] ?? null,
                        'category' => 'nasional',
                        'source' => 'pondok',
                        'is_active' => true,
                    ]
                );

                $gradesByLevel = GradeLevel::where('school_id', $schoolId)->get()->keyBy('level');

                foreach ($def['levels'] as $i => $level) {
                    $track = $def['tracks'][$i] ?? null;
                    $key = $track ? $level.':'.$track : (string) $level;
                    $hours = (int) ($data['allocations'][$key] ?? 0);
                    $gradeId = $gradesByLevel[$level]?->id;

                    if (! $gradeId) { continue; }

                    if ($hours <= 0) {
                        GradeLevelSubject::where('grade_level_id', $gradeId)->where('subject_id', $subject->id)
                            ->when($track, fn ($q) => $q->where('track', $track), fn ($q) => $q->whereNull('track'))->delete();
                        continue;
                    }

                    GradeLevelSubject::updateOrCreate(
                        ['grade_level_id' => $gradeId, 'subject_id' => $subject->id, 'track' => $track],
                        ['allocation_hours' => $hours, 'is_active' => true]
                    );
                }

                // Ganti kode lama bila sedang mengedit.
                if ($editingCode && strtoupper($editingCode) !== strtoupper($data['code'])) {
                    $old = Subject::where('school_id', $schoolId)->where('code', $editingCode)->first();
                    if ($old && $old->id !== $subject->id) {
                        GradeLevelSubject::where('subject_id', $old->id)->delete();
                        $old->delete();
                    }
                }
            }
        });
    }

    private function authorizeSuper(Request $request): void
    {
        $user = $request->user() ?? auth()->user();

        $isSuper = $user && (
            $user->isSystemAdmin()
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || (function_exists('canPermission') && canPermission('system-settings-update'))
        );

        if (! $isSuper) {
            abort(403, 'Hanya Super Admin yang dapat mengelola struktur kurikulum.');
        }
    }
}
