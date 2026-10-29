<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\GtkCrossUnitAssignment;
use App\Models\GtkEmployment;
use App\Models\InstitutionDecree;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Services\InstitutionDecreeMatrixService;
use App\Services\SchoolGroupService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Import SK & Matriks Pembagian Tugas dari workbook 3 sheet:
 *   SK       — satu baris per SK
 *   MATRIKS  — satu baris per guru + mapel + rombel + JP
 *   TUGAS    — satu baris per guru + tugas tambahan
 *
 * Semua referensi (guru, mapel, rombel, tahun ajaran, penandatangan) harus
 * sudah ada di master. Baris yang tidak valid dilaporkan tanpa membatalkan
 * baris lain.
 */
class InstitutionDecreeImport
{
    private School $school;

    private ?AcademicYear $defaultAcademicYear;

    private bool $updateExisting;

    private string $userId;

    private InstitutionDecreeMatrixService $matrix;

    /** @var array<string, array<int, string>> normalized name => user IDs */
    private array $teachersByName = [];

    /** @var array<string, string> normalized nupy => user ID */
    private array $teachersByNupy = [];

    /** @var array<string, array<int, Subject>> */
    private array $subjectsByName = [];

    /** @var array<string, array<int, Subject>> */
    private array $subjectsByCode = [];

    /** @var array<string, array<string, array<int, StudyGroup>>> ayId => name => groups */
    private array $groupsByYear = [];

    /** @var array<string, AcademicYear|null> */
    private array $academicYears = [];

    /** @var array<string, User> */
    private array $signersByName = [];

    /** @var array<string, User> */
    private array $signersByNupy = [];

    private array $errors = [];

    private array $warnings = [];

    private int $created = 0;

    private int $updated = 0;

    private int $assignmentCount = 0;

    private int $taskCount = 0;

    public function __construct(School $school, ?AcademicYear $defaultAcademicYear, bool $updateExisting, string $userId)
    {
        $this->school = $school;
        $this->defaultAcademicYear = $defaultAcademicYear;
        $this->updateExisting = $updateExisting;
        $this->userId = $userId;
        $this->matrix = app(InstitutionDecreeMatrixService::class);

        $this->buildReferenceMaps();
    }

    /**
     * @return array{created:int,updated:int,assignments:int,tasks:int,errors:array,warnings:array}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = $this->loadSpreadsheet($file);
        $skSheet = $this->findSheet($spreadsheet, 'SK');
        $matrixSheet = $this->findSheet($spreadsheet, 'MATRIKS');
        $taskSheet = $this->findSheet($spreadsheet, 'TUGAS');

        // CSV tidak memiliki nama sheet; gunakan sheet tunggal sebagai SK.
        if (! $skSheet && $spreadsheet->getSheetCount() === 1) {
            $skSheet = $spreadsheet->getActiveSheet();
        }

        if (! $skSheet) {
            throw new \RuntimeException('Sheet "SK" tidak ditemukan. Gunakan template import yang disediakan.');
        }

        $skRows = $this->readRows($skSheet);
        $matrixRows = $matrixSheet ? $this->readRows($matrixSheet) : [];
        $taskRows = $taskSheet ? $this->readRows($taskSheet) : [];

        $matrixByDecree = $this->groupByDecreeNumber($matrixRows, 'MATRIKS');
        $tasksByDecree = $this->groupByDecreeNumber($taskRows, 'TUGAS');

        $seenNumbers = [];

        foreach ($skRows as $row) {
            $rowNumber = $row['__row'];
            $number = trim((string) ($row['nomor_sk'] ?? ''));

            if ($number === '' && trim((string) ($row['judul'] ?? '')) === '') {
                continue;
            }

            if ($number === '') {
                $this->errors[] = "Baris SK {$rowNumber}: nomor_sk wajib diisi.";
                continue;
            }

            if (isset($seenNumbers[mb_strtolower($number)])) {
                $this->errors[] = "Baris SK {$rowNumber}: nomor SK {$number} duplikat di dalam file.";
                continue;
            }
            $seenNumbers[mb_strtolower($number)] = true;

            try {
                $this->importOne(
                    $row,
                    $matrixByDecree[mb_strtolower($number)] ?? [],
                    $tasksByDecree[mb_strtolower($number)] ?? [],
                    $matrixSheet !== null,
                    $taskSheet !== null
                );
            } catch (\Throwable $e) {
                $this->errors[] = "Baris SK {$rowNumber} ({$number}): ".$e->getMessage();
            }
        }

        // Baris matriks/tugas yang menunjuk nomor SK tidak dikenal.
        foreach ($matrixByDecree as $number => $rows) {
            if (! isset($seenNumbers[mb_strtolower((string) $number)])) {
                $this->errors[] = "Sheet MATRIKS: nomor SK {$number} tidak ada di sheet SK.";
            }
        }
        foreach ($tasksByDecree as $number => $rows) {
            if (! isset($seenNumbers[mb_strtolower((string) $number)])) {
                $this->errors[] = "Sheet TUGAS: nomor SK {$number} tidak ada di sheet SK.";
            }
        }

        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'assignments' => $this->assignmentCount,
            'tasks' => $this->taskCount,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    private function importOne(array $row, array $matrixRows, array $taskRows, bool $hasMatrixSheet = true, bool $hasTaskSheet = true): void
    {
        $rowNumber = $row['__row'];
        $number = trim((string) $row['nomor_sk']);
        $title = trim((string) ($row['judul'] ?? ''));

        if ($title === '') {
            throw new \RuntimeException('judul wajib diisi.');
        }

        if (mb_strlen($number) > 100) {
            throw new \RuntimeException('nomor_sk maksimal 100 karakter.');
        }

        if (mb_strlen($title) > 255) {
            throw new \RuntimeException('judul maksimal 255 karakter.');
        }

        $issuedDate = $this->parseDate($row['tanggal_ditetapkan'] ?? null);
        if (! $issuedDate) {
            throw new \RuntimeException('tanggal_ditetapkan tidak valid (gunakan format YYYY-MM-DD atau DD/MM/YYYY).');
        }

        $effectiveDate = $this->parseDate($row['tanggal_efektif'] ?? null) ?: $issuedDate;
        $endDate = $this->parseDate($row['tanggal_berakhir'] ?? null);

        if ($endDate && $endDate->lt($effectiveDate)) {
            throw new \RuntimeException('tanggal_berakhir tidak boleh lebih awal dari tanggal_efektif.');
        }

        $academicYear = $this->resolveAcademicYear($row['tahun_ajaran'] ?? null);
        if (! $academicYear) {
            throw new \RuntimeException('tahun_ajaran tidak ditemukan. Isi nama tahun ajaran yang terdaftar.');
        }

        $status = mb_strtolower(trim((string) ($row['status'] ?? 'draft')));
        $status = match ($status) {
            'aktif', 'active' => 'active',
            'arsip', 'archived' => 'archived',
            'draft', '' => 'draft',
            default => $status,
        };

        if (! in_array($status, ['draft', 'active', 'archived'], true)) {
            throw new \RuntimeException('status harus draft, active, atau archived.');
        }

        $decreeType = trim((string) ($row['jenis_sk'] ?? '')) ?: 'SK Pembagian Tugas';
        $signer = $this->resolveSigner($row);

        if ($signer === false) {
            throw new \RuntimeException('penandatangan tidak ditemukan.');
        }

        $existing = InstitutionDecree::where('decree_number', $number)->first();

        if ($existing && (string) $existing->school_id !== (string) $this->school->id) {
            throw new \RuntimeException('nomor SK sudah digunakan oleh satuan pendidikan lain.');
        }

        if ($existing && ! $this->updateExisting) {
            throw new \RuntimeException('nomor SK sudah terdaftar. Aktifkan "Perbarui SK yang sudah ada" bila ingin menimpa.');
        }

        $payload = [
            'decree_number' => $number,
            'decree_type' => $decreeType,
            'title' => $title,
            'description' => trim((string) ($row['deskripsi'] ?? '')) ?: null,
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'issued_date' => $issuedDate->toDateString(),
            'effective_date' => $effectiveDate->toDateString(),
            'end_date' => $endDate?->toDateString(),
            'signed_by' => $signer?->id,
            'signed_position' => trim((string) ($row['jabatan_penandatangan'] ?? '')) ?: null,
            'status' => $status,
        ];

        $assignments = $hasMatrixSheet ? $this->buildAssignments($number, $matrixRows, $academicYear) : null;
        $tasks = $hasTaskSheet ? $this->buildTasks($number, $taskRows, $academicYear) : null;

        DB::transaction(function () use ($existing, $payload, $assignments, $tasks) {
            if ($existing) {
                $existing->update($payload);
                $decree = $existing;
                $this->updated++;
            } else {
                $decree = InstitutionDecree::create($payload);
                $this->created++;
            }

            $result = $this->matrix->sync($decree, $assignments, $tasks, $this->school->id, true);
            $this->assignmentCount += $result['assignments_created'] + $result['assignments_updated'];
            $this->taskCount += $result['tasks_created'] + $result['tasks_updated'];

            foreach ($result['messages'] as $message) {
                $this->warnings[] = "SK {$decree->decree_number}: {$message}";
            }

            $this->matrix->refreshSnapshot($decree->fresh());
        });
    }

    // ─────────────────────────────────────────────────────────────
    // PEMBACAAN SHEET
    // ─────────────────────────────────────────────────────────────

    private function loadSpreadsheet(UploadedFile $file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            $reader = IOFactory::createReader('Csv');
            $reader->setInputEncoding('UTF-8');
        } else {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
        }

        $reader->setReadDataOnly(true);

        return $reader->load($file->getRealPath());
    }

    private function findSheet($spreadsheet, string $name): ?Worksheet
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (mb_strtoupper(trim($sheet->getTitle())) === mb_strtoupper($name)) {
                return $sheet;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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
        $header = str_replace(['*', '(', ')', '/', '-', '?'], ' ', $header);
        $header = preg_replace('/\s+/', '_', $header);

        return trim((string) $header, '_');
    }

    private function groupByDecreeNumber(array $rows, string $sheetName): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $number = trim((string) ($row['nomor_sk'] ?? ''));

            if ($number === '') {
                // Baris contoh pada template sengaja tidak memiliki nomor SK.
                continue;
            }

            $grouped[mb_strtolower($number)][] = $row;
        }

        return $grouped;
    }

    // ─────────────────────────────────────────────────────────────
    // PEMBENTUK PAYLOAD
    // ─────────────────────────────────────────────────────────────

    private function buildAssignments(string $decreeNumber, array $rows, AcademicYear $academicYear): array
    {
        $assignments = [];
        $claimed = [];

        foreach ($rows as $row) {
            $rowNumber = $row['__row'];

            try {
                $teacherId = $this->resolveTeacher($row, $rowNumber);
                $subject = $this->resolveSubject($row, $rowNumber);
                $group = $this->resolveGroup($row, $academicYear, $rowNumber);

                $hours = (int) round((float) ($row['jp'] ?? $row['jam'] ?? 0));
                if ($hours <= 0) {
                    throw new \RuntimeException('jp harus lebih besar dari 0.');
                }

                $pairKey = $subject->id.'|'.$group->id;
                if (isset($claimed[$pairKey]) && (string) $claimed[$pairKey] !== (string) $teacherId && ! $subject->allowsMultipleTeachers()) {
                    throw new \RuntimeException("mapel {$subject->name} pada rombel {$group->name} sudah dipegang GTK lain di file ini.");
                }
                $claimed[$pairKey] = $teacherId;

                $assignments[$teacherId][$subject->id][$group->id] = [
                    'hours' => $hours,
                    'is_coordinator' => $this->parseBool($row['koordinator'] ?? false),
                ];
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet MATRIKS baris {$rowNumber} (SK {$decreeNumber}): ".$e->getMessage();
            }
        }

        return $assignments;
    }

    private function buildTasks(string $decreeNumber, array $rows, AcademicYear $academicYear): array
    {
        $tasks = [];

        foreach ($rows as $row) {
            $rowNumber = $row['__row'];

            try {
                $teacherId = $this->resolveTeacher($row, $rowNumber);
                $name = trim((string) ($row['nama_tugas'] ?? $row['tugas'] ?? ''));

                if ($name === '') {
                    throw new \RuntimeException('nama_tugas wajib diisi.');
                }

                $groupId = null;
                if (trim((string) ($row['rombel'] ?? '')) !== '') {
                    $groupId = $this->resolveGroup($row, $academicYear, $rowNumber)->id;
                }

                $hours = (int) round((float) ($row['jp'] ?? $row['jam'] ?? 0));
                $type = AdditionalTaskType::resolve(
                    null,
                    $name,
                    trim((string) ($row['kode_tugas'] ?? '')) ?: null
                );

                $tasks[] = [
                    'teacher_id' => $teacherId,
                    'task_name' => $type?->nama ?? $name,
                    'task_code' => $type?->kode ?: (trim((string) ($row['kode_tugas'] ?? '')) ?: 'custom-'.strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name))),
                    'weekly_hours' => max(0, $hours),
                    'study_group_id' => $groupId,
                ];
            } catch (\Throwable $e) {
                $this->errors[] = "Sheet TUGAS baris {$rowNumber} (SK {$decreeNumber}): ".$e->getMessage();
            }
        }

        return $tasks;
    }

    // ─────────────────────────────────────────────────────────────
    // RESOLUSI REFERENSI
    // ─────────────────────────────────────────────────────────────

    private function resolveTeacher(array $row, int $rowNumber): string
    {
        $nupy = trim((string) ($row['nupy'] ?? $row['nip'] ?? ''));
        $name = trim((string) ($row['nama_guru'] ?? $row['nama_gtk'] ?? $row['guru'] ?? ''));

        if ($nupy !== '') {
            $id = $this->teachersByNupy[$this->normalizeKey($nupy)] ?? null;
            if ($id) {
                return $id;
            }
        }

        if ($name === '') {
            if ($nupy !== '') {
                throw new \RuntimeException("GTK dengan NUPY {$nupy} tidak ditemukan pada satuan pendidikan ini.");
            }

            throw new \RuntimeException('nama_guru atau nupy wajib diisi.');
        }

        $ids = $this->teachersByName[$this->normalizeName($name)] ?? [];

        if (count($ids) === 1) {
            return $ids[0];
        }

        if (count($ids) > 1) {
            throw new \RuntimeException("nama GTK \"{$name}\" ambigu ({$this->countIds($ids)} kandidat). Gunakan kolom nupy.");
        }

        throw new \RuntimeException("GTK \"{$name}\" tidak ditemukan pada satuan pendidikan ini.");
    }

    private function resolveSubject(array $row, int $rowNumber): Subject
    {
        $code = trim((string) ($row['kode_mapel'] ?? ''));
        $name = trim((string) ($row['mapel'] ?? $row['mata_pelajaran'] ?? ''));

        $candidates = [];
        if ($code !== '') {
            $candidates = $this->subjectsByCode[$this->normalizeKey($code)] ?? [];
        }
        if ($candidates === [] && $name !== '') {
            $candidates = $this->subjectsByName[$this->normalizeName($name)] ?? [];
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        if (count($candidates) > 1) {
            throw new \RuntimeException("mata pelajaran \"{$name}\" ambigu. Gunakan kolom kode_mapel.");
        }

        throw new \RuntimeException("mata pelajaran \"".($name ?: $code)."\" tidak ditemukan.");
    }

    private function resolveGroup(array $row, AcademicYear $academicYear, int $rowNumber): StudyGroup
    {
        $name = trim((string) ($row['rombel'] ?? $row['kelas_rombel'] ?? ''));
        $code = trim((string) ($row['kode_rombel'] ?? ''));
        $grade = trim((string) ($row['kelas'] ?? $row['tingkat'] ?? ''));

        if ($name === '' && $code === '') {
            throw new \RuntimeException('rombel wajib diisi.');
        }

        $groups = $this->groupsForYear($academicYear);
        $candidates = [];

        foreach ($groups as $group) {
            $nameMatch = $name !== '' && $this->normalizeName($group->name) === $this->normalizeName($name);
            $codeMatch = $code !== '' && $this->normalizeKey($group->code ?? '') === $this->normalizeKey($code);

            if ($nameMatch || $codeMatch) {
                $candidates[] = $group;
            }
        }

        if ($grade !== '' && count($candidates) > 1) {
            $gradeKey = $this->normalizeKey($grade);
            $candidates = array_values(array_filter($candidates, function (StudyGroup $group) use ($gradeKey) {
                $level = (string) ($group->gradeLevel->level ?? '');
                $gradeName = $this->normalizeName($group->gradeLevel->name ?? '');

                return $level === $gradeKey || $gradeName === $gradeKey || str_contains($gradeName, $gradeKey);
            }));
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        if (count($candidates) > 1) {
            throw new \RuntimeException("rombel \"{$name}\" ambigu. Isi kolom kelas untuk membedakan.");
        }

        throw new \RuntimeException("rombel \"".($name ?: $code)."\" tidak ditemukan pada tahun ajaran {$academicYear->name}.");
    }

    private function resolveAcademicYear($value): ?AcademicYear
    {
        $value = trim((string) $value);

        if ($value === '') {
            if ($this->defaultAcademicYear) {
                return $this->defaultAcademicYear;
            }

            return AcademicYear::active()->first();
        }

        if (isset($this->academicYears[$value])) {
            return $this->academicYears[$value];
        }

        $year = AcademicYear::whereKey($value)->first();

        if (! $year) {
            $key = $this->normalizeKey($value);
            $year = AcademicYear::all()->first(fn (AcademicYear $candidate) => $this->normalizeKey($candidate->name) === $key);
        }

        return $this->academicYears[$value] = $year;
    }

    /**
     * @return User|false|null  false berarti penandatangan diisi tetapi tidak ditemukan
     */
    private function resolveSigner(array $row)
    {
        $nupy = trim((string) ($row['penandatangan_nupy'] ?? ''));
        $name = trim((string) ($row['penandatangan_nama'] ?? ''));

        if ($nupy !== '') {
            return $this->signersByNupy[$this->normalizeKey($nupy)] ?? false;
        }

        if ($name !== '') {
            return $this->signersByName[$this->normalizeName($name)] ?? false;
        }

        return $this->school->principal_user_id
            ? User::find($this->school->principal_user_id)
            : null;
    }

    private function groupsForYear(AcademicYear $academicYear): array
    {
        $key = (string) $academicYear->id;

        if (! isset($this->groupsByYear[$key])) {
            $this->groupsByYear[$key] = StudyGroup::with('gradeLevel:id,name,level')
                ->where('school_id', $this->school->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('is_active', true)
                ->get()
                ->all();
        }

        return $this->groupsByYear[$key];
    }

    private function buildReferenceMaps(): void
    {
        $userIds = collect(SchoolGroupService::userIdsForSchool($this->school->id))
            ->merge(GtkCrossUnitAssignment::where('school_id', $this->school->id)
                ->when($this->defaultAcademicYear, fn ($q) => $q->where('academic_year_id', $this->defaultAcademicYear->id))
                ->active()
                ->pluck('user_id'))
            ->merge(GtkEmployment::where('school_id', $this->school->id)->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        foreach (User::whereIn('id', $userIds)->get(['id', 'name']) as $user) {
            $this->teachersByName[$this->normalizeName($user->name)][] = (string) $user->id;
        }

        foreach (GtkEmployment::whereIn('user_id', $userIds)->get() as $employment) {
            $nupy = trim((string) ($employment->nupy ?? ''));
            if ($nupy !== '') {
                $this->teachersByNupy[$this->normalizeKey($nupy)] = (string) $employment->user_id;
            }
        }

        $subjects = Subject::where(fn ($q) => $q->where('school_id', $this->school->id)->orWhereNull('school_id'))->get();
        foreach ($subjects as $subject) {
            $this->subjectsByName[$this->normalizeName($subject->name)][] = $subject;
            if ($subject->code) {
                $this->subjectsByCode[$this->normalizeKey($subject->code)][] = $subject;
            }
        }

        $signers = User::whereIn('id', usersHavingPermission('decree.signer.certifiable'))->get();
        foreach ($signers as $signer) {
            $this->signersByName[$this->normalizeName($signer->name)] = $signer;
        }

        $employmentNupy = GtkEmployment::whereIn('user_id', $signers->pluck('id'))->get();
        foreach ($employmentNupy as $employment) {
            $nupy = trim((string) ($employment->nupy ?? ''));
            if ($nupy !== '' && $signers->contains('id', $employment->user_id)) {
                $this->signersByNupy[$this->normalizeKey($nupy)] = $signers->firstWhere('id', $employment->user_id);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // PARSER
    // ─────────────────────────────────────────────────────────────

    private function parseDate($value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_numeric($value) && (float) $value > 0) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd M Y', 'd F Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date;
                }
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = mb_strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'ya', 'y', 'yes', 'x', '✓', 'koordinator'], true);
    }

    private function normalizeName(?string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
    }

    private function normalizeKey(?string $value): string
    {
        return mb_strtolower(preg_replace('/[^a-z0-9]+/i', '', trim((string) $value)));
    }

    private function countIds(array $ids): int
    {
        return count(array_unique($ids));
    }
}
