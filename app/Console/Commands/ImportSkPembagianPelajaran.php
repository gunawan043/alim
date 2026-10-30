<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\InstitutionDecree;
use App\Models\School;
use App\Models\StructuralPosition;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\User;
use App\Services\InstitutionDecreePayloadService;
use App\Services\SchoolGroupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import tabel SK Pembagian Tugas dengan format yang sama seperti halaman
 * institution-decrees/create: positions_json + assignments_json + other_tasks_json.
 *
 * JP mapel diambil dari kurikulum (study_group_subjects), bukan dari tabel,
 * karena halaman create juga memakai sumber itu.
 *
 * Default = dry-run. Tambahkan --commit untuk menyimpan.
 */
class ImportSkPembagianPelajaran extends Command
{
    protected $signature = 'sk:import-pembagian
        {--file=database/data/sk-pembagian-smp-putri-2026-2027.json : Berkas JSON sumber tabel}
        {--school=Smp It Putri : Nama sekolah (pencarian LIKE)}
        {--year= : Tahun ajaran, default tahun ajaran aktif}
        {--decree-number= : Nomor SK (wajib saat --commit)}
        {--decree-date= : Tanggal SK (Y-m-d), default hari ini}
        {--status=draft : Status SK saat dibuat (draft|active)}
        {--commit : Benar-benar menyimpan ke database}';

    protected $description = 'Import SK Pembagian Tugas mengikuti format halaman create (3 payload JSON)';

    /** Label mapel di tabel -> nama mapel master (boleh lebih dari satu). */
    private const SUBJECT_MAP = [
        'adab akhlaq' => ['Adab'],
        'adab' => ['Adab'],
        'aqidah' => ['Aqidah'],
        'fiqih' => ['Fikih'],
        'fikih' => ['Fikih'],
        'hafalan hadits/pai depag' => ['Hafalan Hadits'],
        'hafalan hadits' => ['Hafalan Hadits'],
        'bahasa arab' => ['Bahasa Arab'],
        'qowaid' => ['Qowaid Lughoh'],
        'kitabah khot' => ['Kitabah', 'Khot'],
        'ta\'bir' => ['Ta\'bir'],
        'tahfidzul qur\'an' => ['Tahfidzul Qur\'an'],
        'bhs indonesia' => ['Bahasa Indonesia'],
        'bhs inggris' => ['Bahasa Inggris'],
        'ipa' => ['IPA Terpadu'],
        'matematika' => ['Matematika'],
        'pjok / ips' => ['Penjaskes'],
        'pkn/pjok' => ['PKn / IPS / Seni'],
        'ips' => ['PKn / IPS / Seni'],
        'prakarya / informatika' => ['Informatika'],
        'prakarya/informatika' => ['Informatika'],
    ];

    /** Label jabatan di tabel -> nama structural_positions. */
    private const POSITION_MAP = [
        'kepala sekolah' => 'Kepala Satuan Pendidikan',
        'wakil kepala sekolah' => 'Wakil Kepala Satuan Pendidikan',
        'kepala pengasuhan' => 'Kepala Asrama',
        'bendahara bos dan kepala tata tata usaha' => 'Kepala Tata Usaha',
        'staf tata usaha' => 'Staf Tata Usaha',
        'koordinator sarpras (pencatatan)' => 'Koordinator Sarpras',
    ];

    private array $warn = [
        'teachers_missing' => [],
        'teachers_outside_roster' => [],
        'teachers_fuzzy' => [],
        'subject_missing' => [],
        'conflicts' => [],
        'rombel_missing' => [],
    ];

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');

        $school = School::where('name', 'like', '%'.$this->option('school').'%')->first();
        if (! $school) {
            $this->error('Sekolah tidak ditemukan: '.$this->option('school'));

            return self::FAILURE;
        }

        $year = $this->option('year')
            ? AcademicYear::where('name', $this->option('year'))->first()
            : AcademicYear::where('is_active', true)->first();

        if (! $year) {
            $this->error('Tahun ajaran tidak ditemukan.');

            return self::FAILURE;
        }

        $path = base_path($this->option('file'));
        if (! is_file($path)) {
            $this->error('Berkas tidak ditemukan: '.$path);

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data) || empty($data['teachers'])) {
            $this->error('Format JSON tidak dikenali.');

            return self::FAILURE;
        }

        $this->line('');
        $this->info('SK Pembagian Tugas - '.$school->name.' - '.$year->name);
        $this->line(str_repeat('=', 118));

        $groups = StudyGroup::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($g) => [$g->gradeLevel->level ?? 0, $g->name])
            ->values();

        $groupByName = $groups->keyBy(fn ($g) => strtoupper(str_replace(' ', '', $g->name)));
        $rombelNames = $data['rombel'] ?? [];
        $groupIds = [];
        foreach ($rombelNames as $name) {
            $key = strtoupper(str_replace(' ', '', $name));
            if (isset($groupByName[$key])) {
                $groupIds[$name] = $groupByName[$key]->id;
            } else {
                $this->warn['rombel_missing'][] = $name;
            }
        }

        $this->line('Rombel kurikulum: '.$groups->count().' | rombel di tabel: '.count($groupIds));
        $this->line('');

        $subjects = Subject::where('school_id', $school->id)->get();
        $subjectByName = $subjects->keyBy(fn ($s) => $this->norm($s->name));

        $curriculum = [];
        foreach (StudyGroupSubject::whereIn('study_group_id', $groups->pluck('id'))
            ->where('is_active', true)
            ->get(['subject_id', 'study_group_id', 'weekly_hours']) as $row) {
            $curriculum[$row->subject_id][$row->study_group_id] = (float) $row->weekly_hours;
        }

        $rosterIds = collect(SchoolGroupService::userIdsForSchool($school->id))->map(fn ($id) => (string) $id);
        $users = User::select('id', 'name')->get();
        $userByNorm = $users->keyBy(fn ($u) => $this->normTeacher($u->name));
        $aliases = $data['teacher_aliases'] ?? [];

        $positionsByName = StructuralPosition::active()->get()->keyBy(fn ($p) => mb_strtolower(trim($p->name)));

        // Kunci peta dinormalisasi (apostrof & titik dibuang) agar cocok dengan label tabel.
        $subjectMap = [];
        foreach (self::SUBJECT_MAP as $key => $names) {
            $subjectMap[$this->norm($key)] = $names;
        }
        $positionMap = [];
        foreach (self::POSITION_MAP as $key => $name) {
            $positionMap[$this->norm($key)] = $name;
        }

        $positions = [];
        $assignments = [];
        $tasks = [];
        $rows = [];
        $claimed = [];
        $totalJp = 0;

        foreach ($data['teachers'] as $row) {
            $raw = $row['name'];
            [$userId, $matched, $method] = $this->resolveTeacher($raw, $userByNorm, $users, $aliases);

            if (! $userId) {
                $this->warn['teachers_missing'][] = $raw;

                continue;
            }

            if ($method !== 'exact') {
                $this->warn['teachers_fuzzy'][] = [$raw, $matched];
            }

            if (! $rosterIds->contains($userId)) {
                $this->warn['teachers_outside_roster'][] = $matched;
            }

            $positionName = null;
            $rowSubjects = [];
            $rowTasks = [];

            foreach ($row['lines'] as $line) {
                $label = trim($line['label'] ?? '');
                if ($label === '') {
                    continue;
                }

                $jp = array_map(fn ($v) => (int) $v, $line['jp'] ?? []);

                $subjectNames = $subjectMap[$this->norm($label)] ?? null;

                if ($subjectNames === null) {
                    $posKey = $this->norm($label);
                    if (isset($positionMap[$posKey])) {
                        $positionName = $positionMap[$posKey];

                        continue;
                    }

                    // Hanya "Wali Kelas X & Pembina Y" yang dipecah; label lain
                    // seperti "Koordinator Bahasa Arab & Media" tetap satu tugas.
                    $parts = str_starts_with($this->norm($label), 'wali kelas')
                        ? preg_split('/\s+&\s+/u', $label)
                        : [$label];

                    foreach ($parts as $part) {
                        $part = trim($part);
                        if ($part === '') {
                            continue;
                        }
                        $task = $this->classifyTask($part, $groupIds);
                        if ($task) {
                            $rowTasks[] = $task;
                        }
                    }

                    continue;
                }

                if (array_sum($jp) === 0) {
                    continue;
                }

                foreach ($subjectNames as $subjectName) {
                    $subject = $subjectByName->get($this->norm($subjectName));
                    if (! $subject) {
                        $this->warn['subject_missing'][] = $subjectName;

                        continue;
                    }

                    foreach ($jp as $i => $value) {
                        if ($value <= 0) {
                            continue;
                        }
                        $rombel = $rombelNames[$i] ?? null;
                        $gid = $rombel ? ($groupIds[$rombel] ?? null) : null;
                        if (! $gid) {
                            continue;
                        }

                        $hours = $curriculum[$subject->id][$gid] ?? 0;
                        if ($hours <= 0) {
                            continue;
                        }

                        $key = $subject->id.'|'.$gid;
                        if (isset($claimed[$key]) && ! str_contains(mb_strtolower($subject->name), 'tahfidz')) {
                            $this->warn['conflicts'][] = $subject->name.' / '.$rombel.' : '.$claimed[$key].' (dilewati: '.$matched.')';

                            continue;
                        }
                        $claimed[$key] = $matched;

                        $assignments[$userId][$subject->id][$gid] = (int) $hours;
                        $rowSubjects[$subject->name] = ($rowSubjects[$subject->name] ?? 0) + (int) $hours;
                        $totalJp += (int) $hours;
                    }
                }
            }

            if ($positionName) {
                $position = $positionsByName->get(mb_strtolower(trim($positionName)));
                if ($position) {
                    $positions[$userId] = $position->id;
                } else {
                    $this->warn['subject_missing'][] = 'jabatan tidak ada: '.$positionName;
                }
            }

            foreach ($rowTasks as $task) {
                $tasks[] = [
                    'teacher_id' => $userId,
                    'task_name' => $task['name'],
                    'task_code' => $task['code'],
                    'weekly_hours' => $task['hours'],
                    'study_group_id' => $task['study_group_id'],
                ];
            }

            $rows[] = [
                'no' => $row['no'],
                'name' => $matched,
                'position' => $positionName,
                'subjects' => $rowSubjects,
                'tasks' => $rowTasks,
            ];
        }

        $this->line(str_pad('NO', 4).str_pad('NAMA', 30).str_pad('JABATAN', 30).str_pad('MAPEL (JP)', 34).str_pad('TUGAS (JP)', 30).'TOTAL');
        $this->line(str_repeat('-', 118));

        $taskJp = 0;
        foreach ($rows as $r) {
            $mapelText = $r['subjects']
                ? collect($r['subjects'])->map(fn ($jp, $n) => $n.' '.$jp)->implode(', ')
                : '-';
            $tugasText = $r['tasks']
                ? collect($r['tasks'])->map(fn ($t) => $t['name'].' '.$t['hours'])->implode(', ')
                : '-';

            $jpMapel = array_sum($r['subjects']);
            $jpTugas = array_sum(array_column($r['tasks'], 'hours'));
            $taskJp += $jpTugas;

            $this->line(
                str_pad((string) $r['no'], 4)
                .str_pad(mb_substr($r['name'], 0, 29), 30)
                .str_pad(mb_substr($r['position'] ?? '-', 0, 29), 30)
                .str_pad(mb_substr($mapelText, 0, 33), 34)
                .str_pad(mb_substr($tugasText, 0, 29), 30)
                .($jpMapel + $jpTugas)
            );
        }

        $this->line(str_repeat('-', 118));
        $this->line('');

        if ($this->warn['teachers_fuzzy']) {
            $this->warn('Nama guru dicocokkan ('.count($this->warn['teachers_fuzzy']).')');
            foreach ($this->warn['teachers_fuzzy'] as [$raw, $matched]) {
                $this->line('  '.$raw.'  =>  '.$matched);
            }
            $this->line('');
        }

        if ($this->warn['teachers_missing']) {
            $this->error('Guru tidak ditemukan: '.implode(', ', $this->warn['teachers_missing']));
        }
        if ($this->warn['teachers_outside_roster']) {
            $this->warn('Guru di luar roster sekolah (tidak muncul di halaman create): '.implode(', ', array_unique($this->warn['teachers_outside_roster'])));
        }
        if ($this->warn['conflicts']) {
            $this->warn('Bentrok satu-mapel-satu-guru, sel dilewati ('.count($this->warn['conflicts']).')');
            foreach ($this->warn['conflicts'] as $c) {
                $this->line('  - '.$c);
            }
            $this->line('');
        }
        if ($this->warn['subject_missing']) {
            $this->error('Tidak terpetakan: '.implode(' | ', array_unique($this->warn['subject_missing'])));
        }

        $perRombel = [];
        foreach ($assignments as $subs) {
            foreach ($subs as $sid => $cells) {
                foreach ($cells as $gid => $h) {
                    $perRombel[$gid] = ($perRombel[$gid] ?? 0) + $h;
                }
            }
        }
        $kurikulumPerRombel = [];
        foreach ($curriculum as $sid => $cells) {
            foreach ($cells as $gid => $h) {
                $kurikulumPerRombel[$gid] = ($kurikulumPerRombel[$gid] ?? 0) + $h;
            }
        }

        $this->line('RINGKASAN');
        $this->line('  penugasan mengajar : '.array_sum(array_map(fn ($s) => array_sum(array_map('count', $s)), $assignments)));
        $this->line('  JP mengajar        : '.$totalJp);
        $this->line('  tugas tambahan     : '.count($tasks).' ('.$taskJp.' JP)');
        $this->line('  jabatan diisi      : '.count($positions));
        $this->line('  guru               : '.count($rows));
        $this->line('');

        $this->line('TOTAL JP PER ROMBEL (penugasan / kurikulum)');
        $line = [];
        foreach ($groups as $g) {
            $got = $perRombel[$g->id] ?? 0;
            $need = $kurikulumPerRombel[$g->id] ?? 0;
            $line[] = $g->name.'='.$got.'/'.$need.($got == $need ? '' : ' (!)');
        }
        $this->line('  '.implode('  ', $line));
        $this->line('');

        if (! $commit) {
            $this->info('DRY-RUN: tidak ada data yang disimpan. Jalankan ulang dengan --commit untuk menyimpan.');

            return self::SUCCESS;
        }

        $decreeNumber = $this->option('decree-number');
        if (! $decreeNumber) {
            $this->error('--decree-number wajib diisi saat --commit.');

            return self::FAILURE;
        }

        $status = $this->option('status');
        if (! in_array($status, ['draft', 'active'], true)) {
            $this->error('--status harus draft atau active.');

            return self::FAILURE;
        }

        $issuedDate = $this->option('decree-date') ?: now()->toDateString();

        DB::beginTransaction();

        try {
            $decree = InstitutionDecree::firstOrCreate(
                ['decree_number' => $decreeNumber],
                [
                    'decree_type' => 'SK Pembagian Tugas',
                    'title' => 'Pembagian Tugas Mengajar '.$year->name,
                    'academic_year_id' => $year->id,
                    'school_id' => $school->id,
                    'issued_date' => $issuedDate,
                    'effective_date' => $issuedDate,
                    'signed_position' => 'Kepala Sekolah',
                    'status' => $status,
                ]
            );

            $service = app(InstitutionDecreePayloadService::class);

            $positionCount = $service->savePositions($positions, $school->id);
            $result = $service->saveMatrix($decree, $assignments, $tasks, $school->id);

            DB::commit();

            $this->info('TERSIMPAN.');
            $this->line('  SK              : '.$decree->decree_number.' ('.$decree->status.') - id '.$decree->id);
            $this->line('  jabatan diubah  : '.$positionCount);
            $this->line('  penugasan       : '.$result['assignments']);
            $this->line('  tugas tambahan  : '.$result['tasks']);
            $this->line('  sel dilewati    : '.$result['skipped']);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal menyimpan: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function classifyTask(string $part, array $groupIds): ?array
    {
        $key = $this->norm($part);

        if (str_starts_with($key, 'wali kelas')) {
            $gid = null;
            if (preg_match('/wali kelas\s+(\d)\s*([a-e])/i', $part, $m)) {
                $gid = $groupIds[$m[1].strtoupper($m[2])] ?? null;
            }

            return ['name' => $part, 'code' => 'wali_kelas', 'hours' => 12, 'study_group_id' => $gid];
        }

        if (str_contains($key, 'koordinator kesiswaan')) {
            return ['name' => $part, 'code' => 'kesiswaan', 'hours' => 3, 'study_group_id' => null];
        }

        if (str_starts_with($key, 'koordinator')) {
            return ['name' => $part, 'code' => 'koordinator', 'hours' => 3, 'study_group_id' => null];
        }

        return ['name' => $part, 'code' => 'custom', 'hours' => 3, 'study_group_id' => null];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: string}
     */
    private function resolveTeacher(string $raw, $userByNorm, $users, array $aliases): array
    {
        if (isset($aliases[$raw])) {
            $hit = $users->firstWhere('name', $aliases[$raw]);

            return $hit ? [$hit->id, $hit->name, 'alias'] : [null, null, 'missing'];
        }

        $norm = $this->normTeacher($raw);

        if ($hit = $userByNorm->get($norm)) {
            return [$hit->id, $hit->name, 'exact'];
        }

        if (mb_strlen($norm) >= 5) {
            foreach ($users as $u) {
                $candidate = $this->normTeacher($u->name);
                if (str_starts_with($candidate, $norm) || str_starts_with($norm, $candidate)) {
                    return [$u->id, $u->name, 'prefix'];
                }
            }
        }

        $labelFirst = mb_substr($norm, 0, 4);
        $best = null;
        $bestScore = 0;
        foreach ($users as $u) {
            $candidate = $this->normTeacher($u->name);
            if (mb_substr($candidate, 0, 4) !== $labelFirst) {
                continue;
            }
            similar_text($norm, $candidate, $pct);
            if ($pct > $bestScore) {
                $bestScore = $pct;
                $best = $u;
            }
        }

        if ($best && $bestScore >= 80) {
            return [$best->id, $best->name, 'fuzzy'];
        }

        return [null, null, 'missing'];
    }

    private function norm(string $value): string
    {
        $value = str_replace(["\u{2019}", "'"], '', $value);
        $value = str_replace('.', '', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return mb_strtolower(trim($value));
    }

    private function normTeacher(string $value): string
    {
        $value = preg_replace('/^(ustzh|usth|ustadzah|ustadz|ustad|ust)\b\.?\s*/iu', '', trim($value));
        $value = preg_replace('/,.*$/u', '', $value);

        return $this->norm($value);
    }
}
