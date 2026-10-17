<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentAchievement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentAchievementImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected string $schoolId;

    protected ?string $academicYearId;

    protected array $uploadedFiles;   // filename => \Illuminate\Http\UploadedFile

    protected string $userId;

    protected string $achievementType;

    protected ?string $hafalanCategory;

    protected array $errors = [];

    protected array $successes = [];

    protected array $studentMap = []; // nisn (lowercase) => Student

    public function __construct(
        string $userId,
        string $schoolId,
        string $achievementType,
        ?string $hafalanCategory,
        ?string $academicYearId,
        array $uploadedFiles = []
    ) {
        $this->userId = $userId;
        $this->schoolId = $schoolId;
        $this->achievementType = $achievementType;
        $this->hafalanCategory = $hafalanCategory;
        $this->academicYearId = $academicYearId;
        $this->uploadedFiles = $uploadedFiles;

        // Pre-load students for fast lookup
        $students = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->get(['id', 'nisn', 'name', 'school_id']);
        foreach ($students as $s) {
            if ($s->nisn) {
                $this->studentMap[strtolower($s->nisn)] = $s;
            }
        }
    }

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();
        try {
            $created = 0;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $rowData = $row->toArray();

                // Skip empty rows
                $nisn = trim($rowData['nisn'] ?? '');
                $eventName = trim($rowData['nama_lomba'] ?? '');
                if (blank($nisn) && blank($eventName)) {
                    continue;
                }

                $result = $this->processRow($rowData, $rowNumber);
                if ($result === true) {
                    $created++;
                } else {
                    $this->errors[] = $result;
                }
            }

            if ($created > 0) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = 'Error fatal: '.$e->getMessage();
        }
    }

    public function rules(): array
    {
        return [
            'nisn' => 'required|string|max:30',
            'nama_lomba' => 'required|string|max:191',
            'penyelenggara' => 'nullable|string|max:191',
            'tingkat' => 'nullable|string|max:50',
            'peringkat' => 'nullable|string|max:100',
            'tanggal' => 'nullable|string|max:20',
            'lokasi' => 'nullable|string|max:191',
            'keterangan' => 'nullable|string|max:500',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nisn.required' => 'NISN wajib diisi.',
            'nama_lomba.required' => 'Nama lomba / kompetisi wajib diisi.',
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return count($this->successes);
    }

    public function getSuccesses(): array
    {
        return $this->successes;
    }

    // ─── Row Processing ───────────────────────────────────────────────────

    private function processRow(array $row, int $rowNumber): true|string
    {
        $nisn = trim($row['nisn'] ?? '');
        $eventName = trim($row['nama_lomba'] ?? '');

        if (blank($nisn)) {
            return "Baris {$rowNumber}: NISN wajib diisi.";
        }
        if (blank($eventName)) {
            return "Baris {$rowNumber}: Nama lomba / kompetisi wajib diisi.";
        }

        // Find student by NISN
        $student = $this->studentMap[strtolower($nisn)] ?? null;

        // Try fuzzy name match from optional column
        if (! $student && ! empty($row['nama_siswa'])) {
            $nameRef = strtolower(trim($row['nama_siswa']));
            foreach ($this->studentMap as $s) {
                if (str_contains(strtolower($s->name), $nameRef)) {
                    $student = $s;
                    break;
                }
            }
        }

        if (! $student) {
            return "Baris {$rowNumber}: Siswa dengan NISN '{$nisn}' tidak ditemukan atau tidak aktif.";
        }

        // Normalize level
        $level = $this->normalizeLevel($row['tingkat'] ?? '');
        // Normalize position
        $position = $this->normalizePosition($row['peringkat'] ?? '');
        // Parse date
        $eventDate = $this->parseDate($row['tanggal'] ?? '');
        if (! $eventDate) {
            return "Baris {$rowNumber}: Tanggal tidak valid. Gunakan format DD/MM/YYYY (contoh: 15/03/2024).";
        }

        // Find matching certificate files by NISN
        $certPath = $this->saveMatchedFiles($student, $nisn);

        // Academic year fallback
        $academicYearId = $this->academicYearId;
        if (! $academicYearId) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            $academicYearId = $activeYear?->id;
        }

        $data = [
            'student_id' => $student->id,
            'school_id' => $this->schoolId,
            'academic_year_id' => $academicYearId,
            'achievement_type' => $this->achievementType,
            'hafalan_category' => $this->hafalanCategory,
            'event_name' => $eventName,
            'organizer' => trim($row['penyelenggara'] ?? '') ?: null,
            'level' => $level,
            'position' => $position,
            'position_detail' => $position === 'lainnya' ? (trim($row['peringkat'] ?? '') ?: null) : null,
            'event_date' => $eventDate,
            'event_location' => trim($row['lokasi'] ?? '') ?: null,
            'notes' => trim($row['keterangan'] ?? '') ?: null,
            'created_by' => $this->userId,
        ];

        if ($certPath) {
            $data['certificate_path'] = $certPath;
        }

        StudentAchievement::create($data);

        $this->successes[] = "{$student->name} — {$eventName}";

        return true;
    }

    // ─── File Matching ───────────────────────────────────────────────────

    private function saveMatchedFiles(Student $student, string $nisn): ?string
    {
        $nisnLower = strtolower(trim($nisn));
        $matched = null;

        foreach ($this->uploadedFiles as $filename => $file) {
            $nameOnly = pathinfo($filename, PATHINFO_FILENAME);
            if (strtolower($nameOnly) === $nisnLower) {
                $matched = $file;
                break; // save first match only (single cert path field)
            }
        }

        if (! $matched) {
            return null;
        }

        $dir = 'student-achievements/certificates/'.date('Y/m');
        $ext = strtolower($matched->getClientOriginalExtension());
        $storedName = Str::uuid().'.'.$ext;
        $path = $matched->storeAs($dir, $storedName, 'public');

        return $path;
    }

    // ─── Normalize Helpers ────────────────────────────────────────────────

    private function normalizeLevel(?string $val): string
    {
        $map = [
            'internal' => 'internal',
            'internal sekolah' => 'internal',
            'sekolah' => 'internal',
            'kelas' => 'internal',
            'kecamatan' => 'kecamatan',
            'kec' => 'kecamatan',
            'kabupaten' => 'kabupaten_kota',
            'kabupaten kota' => 'kabupaten_kota',
            'kab' => 'kabupaten_kota',
            'kota' => 'kabupaten_kota',
            'provinsi' => 'provinsi',
            'prov' => 'provinsi',
            'nasional' => 'nasional',
            'nas' => 'nasional',
            'internasional' => 'internasional',
            'international' => 'internasional',
            'internasional' => 'internasional',
        ];
        $v = strtolower(trim($val ?? ''));

        return $map[$v] ?? 'internal';
    }

    private function normalizePosition(?string $val): string
    {
        $v = strtolower(trim($val ?? ''));
        $map = [
            '1' => 'juara_1', 'juara 1' => 'juara_1', 'juara i' => 'juara_1', 'gold' => 'juara_1',
            '2' => 'juara_2', 'juara 2' => 'juara_2', 'juara ii' => 'juara_2', 'silver' => 'juara_2',
            '3' => 'juara_3', 'juara 3' => 'juara_3', 'juara iii' => 'juara_3', 'bronze' => 'juara_3',
            'harapan 1' => 'harapan_1', 'harapan1' => 'harapan_1', 'harapan i' => 'harapan_1',
            'harapan 2' => 'harapan_2', 'harapan2' => 'harapan_2', 'harapan ii' => 'harapan_2',
            'harapan 3' => 'harapan_3', 'harapan3' => 'harapan_3', 'harapan iii' => 'harapan_3',
            'peserta' => 'peserta',
            'finalis' => 'peserta',
            'participan' => 'peserta',
        ];

        return $map[$v] ?? 'lainnya';
    }

    private function parseDate(?string $val): ?string
    {
        if (blank($val)) {
            return date('Y-m-d');
        }

        // Try DD/MM/YYYY
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $val, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        // Try DD-MM-YYYY
        if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{4})$#', $val, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        // Try YYYY-MM-DD
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $val, $m)) {
            return $val;
        }

        return null;
    }
}
