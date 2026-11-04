<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\PerangkatPembelajaran;
use App\Models\Prosem;
use App\Models\Prota;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use App\Services\Kurikulum\PerangkatFormatResolver;
use App\Services\ProsemService;
use App\Services\ProtaService;
use App\Services\SchoolGroupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Import ekosistem Kurikulum dari workbook multi-sheet:
 * CP → TP → ATP → ATP_ITEM → PERANGKAT → PROTA → PROSEM.
 *
 * Referensi dicocokkan ke master sekolah + tahun ajaran; tidak membuat mapel,
 * kelas, guru, atau jenis dokumen baru.
 */
class KurikulumImport
{
    private School $school;

    private ?AcademicYear $defaultAcademicYear;

    private ?string $defaultSemester;

    private bool $updateExisting;

    private User $user;

    /** @var array<string, AcademicYear> */
    private array $academicYears = [];

    /** @var array<string, array<int, Subject>> */
    private array $subjectsByName = [];

    /** @var array<string, array<int, Subject>> */
    private array $subjectsByCode = [];

    /** @var array<int, GradeLevel> */
    private array $grades = [];

    /** @var array<string, array<int, GradeLevel>> */
    private array $gradesByKey = [];

    /** @var array<string, array<int, User>> */
    private array $usersByName = [];

    /** @var array<string, array<int, StudyGroup>> */
    private array $groupsByYear = [];

    private array $errors = [];

    private array $warnings = [];

    private array $stats = [
        'cp_created' => 0, 'cp_updated' => 0,
        'tp_created' => 0, 'tp_updated' => 0,
        'atp_created' => 0, 'atp_updated' => 0, 'atp_items' => 0,
        'perangkat_created' => 0, 'perangkat_updated' => 0,
        'prota_created' => 0, 'prota_updated' => 0,
        'prosem_created' => 0, 'prosem_updated' => 0,
    ];

    private array $cpCache = [];

    private array $tpCache = [];

    private array $atpCache = [];

    public function __construct(
        School $school,
        ?AcademicYear $defaultAcademicYear,
        ?string $defaultSemester,
        bool $updateExisting,
        User $user
    ) {
        $this->school = $school;
        $this->defaultAcademicYear = $defaultAcademicYear;
        $this->defaultSemester = in_array($defaultSemester, ['ganjil', 'genap'], true) ? $defaultSemester : 'ganjil';
        $this->updateExisting = $updateExisting;
        $this->user = $user;

        $this->buildReferences();
    }

    /**
     * @return array{stats:array,errors:array,warnings:array}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = $this->loadSpreadsheet($file);
        $sheetNames = collect($spreadsheet->getAllSheets())->map(fn ($sheet) => mb_strtoupper(trim($sheet->getTitle())))->all();

        $allowed = ['CP', 'TP', 'ATP', 'ATP_ITEM', 'PERANGKAT', 'PROTA', 'PROSEM'];
        if (empty(array_intersect($sheetNames, $allowed))) {
            throw new \RuntimeException('Tidak ada sheet Kurikulum yang dikenali (CP, TP, ATP, ATP_ITEM, PERANGKAT, PROTA, PROSEM).');
        }

        $this->processCp($this->rows($spreadsheet, 'CP'));
        $this->processTp($this->rows($spreadsheet, 'TP'));
        $this->processAtp($this->rows($spreadsheet, 'ATP'));
        $this->processAtpItems($this->rows($spreadsheet, 'ATP_ITEM'));
        $this->processPerangkat($this->rows($spreadsheet, 'PERANGKAT'));
        $this->processProta($this->rows($spreadsheet, 'PROTA'));
        $this->processProsem($this->rows($spreadsheet, 'PROSEM'));

        return [
            'stats' => $this->stats,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // PROSES PER SHEET
    // ─────────────────────────────────────────────────────────────

    private function processCp(array $rows): void
    {
        foreach ($rows as $row) {
            $rowNumber = $row['__row'];

            try {
                $ay = $this->resolveAcademicYear($row);
                $subject = $this->resolveSubject($row);
                $fase = trim((string) ($row['fase'] ?? ''));
                $elemen = trim((string) ($row['elemen'] ?? '')) ?: null;
                $deskripsi = trim((string) ($row['deskripsi'] ?? ''));

                if ($fase === '') {
                    throw new \RuntimeException('fase wajib diisi.');
                }
                if ($deskripsi === '') {
                    throw new \RuntimeException('deskripsi wajib diisi.');
                }

                $query = CapaianPembelajaran::where('school_id', $this->school->id)
                    ->where('subject_id', $subject->id)
                    ->where('academic_year_id', $ay->id)
                    ->where('fase', $fase);
                $elemen === null ? $query->whereNull('elemen') : $query->where('elemen', $elemen);
                $existing = $query->first();

                if ($existing && ! $this->updateExisting) {
                    throw new \RuntimeException('CP dengan mapel/fase/elemen yang sama sudah ada. Aktifkan "Perbarui data lama".');
                }

                $payload = [
                    'subject_id' => $subject->id,
                    'academic_year_id' => $ay->id,
                    'fase' => $fase,
                    'elemen' => $elemen,
                    'deskripsi' => $deskripsi,
                    'urutan' => (int) ($row['urutan'] ?? 0),
                    'is_active' => $this->boolean($row['aktif'] ?? true),
                ];

                if ($existing) {
                    $existing->update($payload);
                    $this->stats['cp_updated']++;
                } else {
                    CapaianPembelajaran::create(array_merge($payload, [
                        'school_id' => $this->school->id,
                        'created_by' => $this->user->id,
                    ]));
                    $this->stats['cp_created']++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet CP baris {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    private function processTp(array $rows): void
    {
        foreach ($rows as $row) {
            $rowNumber = $row['__row'];

            try {
                $ay = $this->resolveAcademicYear($row);
                $semester = $this->resolveSemester($row);
                $subject = $this->resolveSubject($row);
                $grade = $this->resolveGrade($row);
                $kode = trim((string) ($row['kode_tp'] ?? ''));
                $deskripsi = trim((string) ($row['deskripsi'] ?? ''));

                if ($kode === '') {
                    throw new \RuntimeException('kode_tp wajib diisi.');
                }
                if ($deskripsi === '') {
                    throw new \RuntimeException('deskripsi wajib diisi.');
                }

                $cp = $this->resolveCp($row, $subject, $ay, $grade);
                $fase = trim((string) ($row['fase'] ?? '')) ?: $cp?->fase ?: $grade?->fase;
                $elemen = trim((string) ($row['elemen'] ?? '')) ?: $cp?->elemen;

                $query = TujuanPembelajaran::where('school_id', $this->school->id)
                    ->where('subject_id', $subject->id)
                    ->where('academic_year_id', $ay->id)
                    ->where('semester', $semester)
                    ->where('kode_tp', $kode);
                $grade ? $query->where('grade_level_id', $grade->id) : $query->whereNull('grade_level_id');
                $existing = $query->first();

                if ($existing && ! $this->updateExisting) {
                    throw new \RuntimeException("TP {$kode} sudah ada. Aktifkan \"Perbarui data lama\".");
                }

                $kbc = trim((string) ($row['kbc_values'] ?? ''));
                $payload = [
                    'capaian_pembelajaran_id' => $cp?->id,
                    'subject_id' => $subject->id,
                    'grade_level_id' => $grade?->id,
                    'academic_year_id' => $ay->id,
                    'semester' => $semester,
                    'fase' => $fase ?: null,
                    'elemen' => $elemen ?: null,
                    'kode_tp' => $kode,
                    'deskripsi' => $deskripsi,
                    'kbc_values' => $kbc !== '' ? array_values(array_filter(array_map('trim', preg_split('/[;,|]/', $kbc)))) : [],
                    'kbc_indicator' => trim((string) ($row['kbc_indicator'] ?? '')) ?: null,
                    'alokasi_waktu' => max(1, (int) ($row['alokasi_waktu'] ?? 2)),
                    'urutan' => (int) ($row['urutan'] ?? 0),
                    'is_active' => $this->boolean($row['aktif'] ?? true),
                ];

                if ($existing) {
                    $existing->update($payload);
                    $this->stats['tp_updated']++;
                } else {
                    TujuanPembelajaran::create(array_merge($payload, [
                        'school_id' => $this->school->id,
                        'created_by' => $this->user->id,
                    ]));
                    $this->stats['tp_created']++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet TP baris {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    private function processAtp(array $rows): void
    {
        foreach ($rows as $row) {
            $rowNumber = $row['__row'];

            try {
                $ay = $this->resolveAcademicYear($row);
                $semester = $this->resolveSemester($row);
                $subject = $this->resolveSubject($row);
                $grade = $this->resolveGrade($row);
                $teacher = $this->resolveTeacher($row) ?: $this->user;

                $query = AlurTujuanPembelajaran::where('school_id', $this->school->id)
                    ->where('academic_year_id', $ay->id)
                    ->where('semester', $semester)
                    ->where('subject_id', $subject->id);
                $grade ? $query->where('grade_level_id', $grade->id) : $query->whereNull('grade_level_id');
                $existing = $query->first();

                if ($existing && ! $this->updateExisting) {
                    throw new \RuntimeException('ATP untuk mapel/kelas/TA/semester ini sudah ada. Aktifkan "Perbarui data lama".');
                }

                $payload = [
                    'academic_year_id' => $ay->id,
                    'semester' => $semester,
                    'subject_id' => $subject->id,
                    'grade_level_id' => $grade?->id,
                    'fase' => $grade?->fase ?? (trim((string) ($row['fase'] ?? '')) ?: null),
                    'teacher_id' => $teacher->id,
                    'status' => $this->normalizeStatus($row['status'] ?? 'draft', ['draft', 'published']),
                    'catatan' => trim((string) ($row['catatan'] ?? '')) ?: null,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $this->stats['atp_updated']++;
                } else {
                    AlurTujuanPembelajaran::create(array_merge($payload, [
                        'school_id' => $this->school->id,
                        'created_by' => $this->user->id,
                        'total_jp' => 0,
                    ]));
                    $this->stats['atp_created']++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet ATP baris {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    private function processAtpItems(array $rows): void
    {
        $groups = [];
        foreach ($rows as $row) {
            try {
                $key = $this->atpKey($row);
                $groups[$key][] = $row;
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet ATP_ITEM baris {$row['__row']}: ".$e->getMessage();
            }
        }

        foreach ($groups as $key => $groupRows) {
            $first = $groupRows[0];

            try {
                $ay = $this->resolveAcademicYear($first);
                $semester = $this->resolveSemester($first);
                $subject = $this->resolveSubject($first);
                $grade = $this->resolveGrade($first);
                $atp = $this->findAtp($subject, $grade, $ay, $semester);

                if (! $atp) {
                    throw new \RuntimeException('ATP belum ada. Import sheet ATP terlebih dahulu.');
                }

                $seen = [];
                $items = [];
                foreach ($groupRows as $row) {
                    $kode = trim((string) ($row['kode_tp'] ?? ''));
                    $tp = $this->findTp($subject, $grade, $ay, $semester, $kode);
                    if (! $tp) {
                        throw new \RuntimeException("TP {$kode} tidak ditemukan untuk ATP ini.");
                    }
                    if (isset($seen[$tp->id])) {
                        throw new \RuntimeException("TP {$kode} duplikat pada sheet ATP_ITEM.");
                    }
                    $seen[$tp->id] = true;
                    $items[] = [
                        'tujuan_pembelajaran_id' => $tp->id,
                        'urutan' => (int) ($row['urutan'] ?? count($items) + 1),
                        'jp_alokasi' => max(0, (int) ($row['jp'] ?? 0)),
                        'catatan' => trim((string) ($row['catatan'] ?? '')) ?: null,
                    ];
                }

                DB::transaction(function () use ($atp, $items) {
                    $atp->items()->delete();
                    foreach ($items as $item) {
                        $atp->items()->create($item);
                    }
                    $atp->forceFill(['total_jp' => $atp->items()->sum('jp_alokasi')])->save();
                });

                $this->stats['atp_items'] += count($items);
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet ATP_ITEM ({$key}): ".$e->getMessage();
            }
        }
    }

    private function processPerangkat(array $rows): void
    {
        foreach ($rows as $row) {
            $rowNumber = $row['__row'];

            try {
                $ay = $this->resolveAcademicYear($row);
                $semester = $this->resolveSemester($row);
                $subject = $this->resolveSubject($row);
                $grade = $this->resolveGrade($row);
                $atp = $this->findAtp($subject, $grade, $ay, $semester);

                if ($atp === null) {
                    throw new \RuntimeException('ATP belum ada untuk mapel/kelas/TA/semester ini.');
                }

                if (app(PerangkatFormatResolver::class)->resolve($subject) === PerangkatFormatResolver::TAHFIDZ) {
                    throw new \RuntimeException('Perangkat mapel Tahfidz disusun melalui modul RPP Tahfizh, bukan import ini.');
                }

                $judul = trim((string) ($row['judul'] ?? ''));
                if ($judul === '') {
                    throw new \RuntimeException('judul wajib diisi.');
                }

                $teacher = $this->resolveTeacher($row) ?: $this->user;
                $group = $this->resolveGroup($row, $ay);
                $tipe = $this->normalizeStatus($row['tipe'] ?? 'umum', ['umum', 'agama']);
                $status = $this->normalizeStatus($row['status'] ?? 'draft', ['draft', 'final']);

                $existing = PerangkatPembelajaran::where('school_id', $this->school->id)
                    ->where('academic_year_id', $ay->id)
                    ->where('semester', $semester)
                    ->where('subject_id', $subject->id)
                    ->where('grade_level_id', $grade?->id)
                    ->where('is_current', true)
                    ->first();

                if ($existing && ! $this->updateExisting) {
                    throw new \RuntimeException('Perangkat untuk konteks ini sudah ada. Aktifkan "Perbarui data lama".');
                }
                if ($existing && $existing->isLocked()) {
                    throw new \RuntimeException('Perangkat sudah final/terkunci dan tidak dapat diubah lewat import.');
                }

                $desain = $existing?->desain ?: PerangkatPembelajaran::defaultDesain();
                $rawDesain = trim((string) ($row['desain_json'] ?? ''));
                if ($rawDesain !== '') {
                    $decoded = json_decode($rawDesain, true);
                    if (! is_array($decoded)) {
                        throw new \RuntimeException('desain_json tidak valid.');
                    }
                    $desain = array_merge($desain, array_intersect_key($decoded, $desain));
                }

                $payload = [
                    'atp_id' => $atp->id,
                    'study_group_id' => $group?->id,
                    'teacher_id' => $teacher->id,
                    'judul' => $judul,
                    'tipe' => $tipe,
                    'status' => $status,
                    'catatan' => trim((string) ($row['catatan'] ?? '')) ?: null,
                    'desain' => $desain,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $this->stats['perangkat_updated']++;
                } else {
                    PerangkatPembelajaran::create(array_merge($payload, [
                        'school_id' => $this->school->id,
                        'academic_year_id' => $ay->id,
                        'semester' => $semester,
                        'subject_id' => $subject->id,
                        'grade_level_id' => $grade?->id,
                        'source' => 'import',
                        'is_current' => true,
                        'version' => 1,
                        'created_by' => $this->user->id,
                    ]));
                    $this->stats['perangkat_created']++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet PERANGKAT baris {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    private function processProta(array $rows): void
    {
        foreach ($rows as $row) {
            $rowNumber = $row['__row'];

            try {
                $ay = $this->resolveAcademicYear($row);
                $semester = $this->resolveSemester($row);
                $subject = $this->resolveSubject($row);
                $grade = $this->resolveGrade($row);
                $atp = $this->findAtp($subject, $grade, $ay, $semester);
                if (! $atp) {
                    throw new \RuntimeException('ATP belum ada untuk membuat PROTA.');
                }

                $teacher = $this->resolveTeacher($row) ?: ($atp->teacher ?? $this->user);
                $status = $this->normalizeStatus($row['status'] ?? 'draft', ['draft', 'final']);

                $query = Prota::where('school_id', $this->school->id)
                    ->where('academic_year_id', $ay->id)
                    ->where('semester', $semester)
                    ->where('subject_id', $subject->id);
                $grade ? $query->where('grade_level_id', $grade->id) : $query->whereNull('grade_level_id');
                $prota = $query->first();

                if ($prota && ! $this->updateExisting) {
                    throw new \RuntimeException('PROTA sudah ada. Aktifkan "Perbarui data lama".');
                }

                if ($prota) {
                    $prota->update([
                        'teacher_id' => $teacher->id,
                        'atp_id' => $atp->id,
                        'status' => $status,
                        'catatan' => trim((string) ($row['catatan'] ?? '')) ?: null,
                    ]);
                    app(ProtaService::class)->sync($prota->fresh(), true);
                    $this->stats['prota_updated']++;
                } else {
                    $prota = app(ProtaService::class)->createFromAtp($atp, $this->user);
                    $prota->update([
                        'teacher_id' => $teacher->id,
                        'status' => $status,
                        'catatan' => trim((string) ($row['catatan'] ?? '')) ?: null,
                    ]);
                    $this->stats['prota_created']++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet PROTA baris {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    private function processProsem(array $rows): void
    {
        foreach ($rows as $row) {
            $rowNumber = $row['__row'];

            try {
                $ay = $this->resolveAcademicYear($row);
                $semester = $this->resolveSemester($row);
                $subject = $this->resolveSubject($row);
                $grade = $this->resolveGrade($row);

                $query = Prota::where('school_id', $this->school->id)
                    ->where('academic_year_id', $ay->id)
                    ->where('semester', $semester)
                    ->where('subject_id', $subject->id);
                $grade ? $query->where('grade_level_id', $grade->id) : $query->whereNull('grade_level_id');
                $prota = $query->first();

                if (! $prota) {
                    throw new \RuntimeException('PROTA belum ada. Import sheet PROTA terlebih dahulu.');
                }

                $teacher = $this->resolveTeacher($row) ?: ($prota->teacher ?? $this->user);
                $status = $this->normalizeStatus($row['status'] ?? 'draft', ['draft', 'final']);
                $prosem = Prosem::where('prota_id', $prota->id)->first();

                if ($prosem && ! $this->updateExisting) {
                    throw new \RuntimeException('PROSEM sudah ada. Aktifkan "Perbarui data lama".');
                }

                if ($prosem) {
                    $prosem->update([
                        'teacher_id' => $teacher->id,
                        'status' => $status,
                        'catatan' => trim((string) ($row['catatan'] ?? '')) ?: null,
                    ]);
                    app(ProsemService::class)->sync($prosem->fresh(), true);
                    $this->stats['prosem_updated']++;
                } else {
                    $prosem = app(ProsemService::class)->createFromProta($prota, $this->user);
                    $prosem->update([
                        'teacher_id' => $teacher->id,
                        'status' => $status,
                        'catatan' => trim((string) ($row['catatan'] ?? '')) ?: null,
                    ]);
                    $this->stats['prosem_created']++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet PROSEM baris {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // RESOLUSI REFERENSI
    // ─────────────────────────────────────────────────────────────

    private function resolveAcademicYear(array $row): AcademicYear
    {
        $value = trim((string) ($row['tahun_ajaran'] ?? ''));

        if ($value === '') {
            if ($this->defaultAcademicYear) {
                return $this->defaultAcademicYear;
            }
            $active = AcademicYear::where('is_active', true)->first();
            if ($active) {
                return $active;
            }
            throw new \RuntimeException('tahun_ajaran wajib diisi.');
        }

        if (isset($this->academicYears[$value])) {
            return $this->academicYears[$value];
        }

        $year = AcademicYear::whereKey($value)->first();
        if (! $year) {
            $key = $this->normalizeKey($value);
            $year = collect($this->academicYears)->first(fn (AcademicYear $candidate) => $this->normalizeKey($candidate->name) === $key);
        }

        if (! $year) {
            $year = AcademicYear::all()->first(fn (AcademicYear $candidate) => $this->normalizeKey($candidate->name) === $this->normalizeKey($value));
        }

        if (! $year) {
            throw new \RuntimeException("Tahun ajaran \"{$value}\" tidak ditemukan.");
        }

        return $this->academicYears[$value] = $year;
    }

    private function resolveSemester(array $row): string
    {
        $value = mb_strtolower(trim((string) ($row['semester'] ?? '')));
        $value = match ($value) {
            'ganjil', '1', 'semester 1' => 'ganjil',
            'genap', '2', 'semester 2' => 'genap',
            '' => $this->defaultSemester,
            default => $value,
        };

        if (! in_array($value, ['ganjil', 'genap'], true)) {
            throw new \RuntimeException('semester harus ganjil atau genap.');
        }

        return $value;
    }

    private function resolveSubject(array $row): Subject
    {
        $code = $this->normalizeKey($row['kode_mapel'] ?? '');
        $name = $this->normalizeName($row['mapel'] ?? '');

        $candidates = [];
        if ($code !== '') {
            $candidates = $this->subjectsByCode[$code] ?? [];
        }
        if ($candidates === [] && $name !== '') {
            $candidates = $this->subjectsByName[$name] ?? [];
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }
        if (count($candidates) > 1) {
            throw new \RuntimeException('mata pelajaran ambigu. Gunakan kolom kode_mapel.');
        }

        throw new \RuntimeException('mata pelajaran "'.($row['mapel'] ?? $row['kode_mapel'] ?? '-').'" tidak ditemukan.');
    }

    private function resolveGrade(array $row): ?GradeLevel
    {
        $value = trim((string) ($row['kelas'] ?? ''));
        if ($value === '') {
            return null;
        }

        $key = $this->normalizeKey($value);
        $candidates = $this->gradesByKey[$key] ?? [];

        if ($candidates === [] && preg_match('/^(\d+)/', $value, $m)) {
            $candidates = $this->gradesByKey[$m[1]] ?? [];
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }
        if (count($candidates) > 1) {
            throw new \RuntimeException("kelas \"{$value}\" ambigu.");
        }

        throw new \RuntimeException("kelas \"{$value}\" tidak ditemukan.");
    }

    private function resolveTeacher(array $row): ?User
    {
        $name = trim((string) ($row['guru'] ?? ''));
        if ($name === '') {
            return null;
        }

        $candidates = $this->usersByName[$this->normalizeName($name)] ?? [];
        if (count($candidates) === 1) {
            return $candidates[0];
        }
        if (count($candidates) > 1) {
            throw new \RuntimeException("guru \"{$name}\" ambigu.");
        }

        throw new \RuntimeException("guru \"{$name}\" tidak ditemukan pada satuan pendidikan ini.");
    }

    private function resolveGroup(array $row, AcademicYear $ay): ?StudyGroup
    {
        $name = trim((string) ($row['rombel'] ?? ''));
        if ($name === '') {
            return null;
        }

        $groups = $this->groupsByYear[$ay->id] ?? [];
        $key = $this->normalizeKey($name);

        foreach ($groups as $group) {
            if ($this->normalizeKey($group->name) === $key || $this->normalizeKey($group->code ?? '') === $key) {
                return $group;
            }
        }

        throw new \RuntimeException("rombel \"{$name}\" tidak ditemukan pada tahun ajaran {$ay->name}.");
    }

    private function resolveCp(array $row, Subject $subject, AcademicYear $ay, ?GradeLevel $grade): ?CapaianPembelajaran
    {
        $raw = trim((string) ($row['cp'] ?? ''));
        $fase = trim((string) ($row['fase'] ?? '')) ?: $grade?->fase;
        $elemen = trim((string) ($row['elemen'] ?? '')) ?: null;

        if ($raw !== '') {
            $parts = array_map('trim', explode('|', $raw));
            if (count($parts) >= 3) {
                $fase = $parts[1] ?: $fase;
                $elemen = $parts[2] ?: $elemen;
            } elseif (count($parts) === 2) {
                $fase = $parts[0] ?: $fase;
                $elemen = $parts[1] ?: $elemen;
            }
        }

        if (! $fase) {
            return null;
        }

        return CapaianPembelajaran::where('school_id', $this->school->id)
            ->where('subject_id', $subject->id)
            ->where('academic_year_id', $ay->id)
            ->where('fase', $fase)
            ->when($elemen !== null, fn ($q) => $q->where('elemen', $elemen), fn ($q) => $q->whereNull('elemen'))
            ->orderBy('urutan')
            ->first();
    }

    private function findTp(Subject $subject, ?GradeLevel $grade, AcademicYear $ay, string $semester, string $kode): ?TujuanPembelajaran
    {
        $key = implode('|', [$subject->id, $grade?->id ?? '-', $ay->id, $semester, $kode]);
        if (array_key_exists($key, $this->tpCache)) {
            return $this->tpCache[$key];
        }

        $query = TujuanPembelajaran::where('school_id', $this->school->id)
            ->where('subject_id', $subject->id)
            ->where('academic_year_id', $ay->id)
            ->where('semester', $semester)
            ->where('kode_tp', $kode);
        $grade ? $query->where('grade_level_id', $grade->id) : $query->whereNull('grade_level_id');

        return $this->tpCache[$key] = $query->first();
    }

    private function findAtp(Subject $subject, ?GradeLevel $grade, AcademicYear $ay, string $semester): ?AlurTujuanPembelajaran
    {
        $key = implode('|', [$subject->id, $grade?->id ?? '-', $ay->id, $semester]);
        if (array_key_exists($key, $this->atpCache)) {
            return $this->atpCache[$key];
        }

        $query = AlurTujuanPembelajaran::where('school_id', $this->school->id)
            ->where('academic_year_id', $ay->id)
            ->where('semester', $semester)
            ->where('subject_id', $subject->id);
        $grade ? $query->where('grade_level_id', $grade->id) : $query->whereNull('grade_level_id');

        return $this->atpCache[$key] = $query->first();
    }

    private function atpKey(array $row): string
    {
        $ay = $this->resolveAcademicYear($row);
        $semester = $this->resolveSemester($row);
        $subject = $this->resolveSubject($row);
        $grade = $this->resolveGrade($row);

        return implode('|', [$ay->name, $semester, $subject->code ?: $subject->name, $grade?->name ?? '-']);
    }

    // ─────────────────────────────────────────────────────────────
    // PEMBACAAN & REFERENSI
    // ─────────────────────────────────────────────────────────────

    private function loadSpreadsheet(UploadedFile $file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $reader = $extension === 'csv'
            ? IOFactory::createReader('Csv')
            : IOFactory::createReaderForFile($file->getRealPath());

        $reader->setReadDataOnly(true);

        if ($extension === 'csv') {
            $reader->setInputEncoding('UTF-8');
        }

        return $reader->load($file->getRealPath());
    }

    private function rows($spreadsheet, string $name): array
    {
        $sheet = null;
        foreach ($spreadsheet->getAllSheets() as $candidate) {
            if (mb_strtoupper(trim($candidate->getTitle())) === $name) {
                $sheet = $candidate;
                break;
            }
        }

        if (! $sheet) {
            return [];
        }

        return $this->readRows($sheet);
    }

    private function readRows(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        $raw = $sheet->rangeToArray('A1:'.$highestColumn.$highestRow, null, true, false, false);

        if (empty($raw)) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader($header), $raw[0]);
        $rows = [];

        foreach (array_slice($raw, 1) as $index => $values) {
            $row = ['__row' => $index + 2];
            foreach ($headers as $column => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = $values[$column] ?? null;
            }

            $hasValue = collect($row)->except('__row')->contains(fn ($value) => $value !== null && trim((string) $value) !== '');
            if ($hasValue) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function normalizeHeader($header): string
    {
        $header = mb_strtolower(trim((string) $header));
        $header = str_replace(['*', '(', ')', '/', '-', '?', '%'], ' ', $header);
        $header = preg_replace('/\s+/', '_', $header);

        return trim((string) $header, '_');
    }

    private function buildReferences(): void
    {
        $this->academicYears = AcademicYear::all()->mapWithKeys(fn ($ay) => [$ay->id => $ay])->all();

        $subjects = Subject::where(fn ($q) => $q->where('school_id', $this->school->id)->orWhereNull('school_id'))->get();
        foreach ($subjects as $subject) {
            $this->subjectsByName[$this->normalizeName($subject->name)][] = $subject;
            if ($subject->code) {
                $this->subjectsByCode[$this->normalizeKey($subject->code)][] = $subject;
            }
        }

        $this->grades = GradeLevel::where('school_id', $this->school->id)->orderBy('level')->get()->all();
        foreach ($this->grades as $grade) {
            foreach ([$grade->name, $grade->code, $grade->level] as $key) {
                if (filled($key)) {
                    $this->gradesByKey[$this->normalizeKey($key)][] = $grade;
                }
            }
        }

        $userIds = SchoolGroupService::userIdsForSchool($this->school->id);
        foreach (User::whereIn('id', $userIds)->get() as $user) {
            $this->usersByName[$this->normalizeName($user->name)][] = $user;
        }

        $groups = StudyGroup::where('school_id', $this->school->id)->get();
        foreach ($groups as $group) {
            $this->groupsByYear[$group->academic_year_id][] = $group;
        }
    }

    private function normalizeStatus($value, array $allowed): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = match ($value) {
            'aktif', 'active', 'terbit', 'published' => 'published',
            'akhir', 'final' => 'final',
            'draft', '' => 'draft',
            default => $value,
        };

        if (! in_array($value, $allowed, true)) {
            throw new \RuntimeException('status tidak valid: '.$value);
        }

        return $value;
    }

    private function boolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'ya', 'y', 'yes', 'aktif', 'active'], true);
    }

    private function normalizeName($value): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
    }

    private function normalizeKey($value): string
    {
        return mb_strtolower(preg_replace('/[^a-z0-9]+/i', '', trim((string) $value)));
    }
}
