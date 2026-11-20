<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\JadwalKbm;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Services\JadwalGeneratorService;
use App\Services\TeacherAvailabilityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import jadwal pelajaran dari template XLSX/CSV.
 *
 * Satu baris = satu pertemuan (rombel + hari + jam ke + mapel + guru).
 * Referensi rombel/mapel/guru dicocokkan ke data sekolah; tidak membuat
 * rombel, mapel, atau guru baru.
 */
class JadwalKbmImport implements ToCollection, WithHeadingRow, WithMultipleSheets
{
    private School $school;

    private AcademicYear $academicYear;

    private ?User $user;

    private bool $updateExisting;

    private bool $defaultActive;

    /** @var array{created:int,updated:int,skipped:int} */
    private array $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

    /** @var array<int, string> */
    private array $errors = [];

    /** @var array<int, string> */
    private array $warnings = [];

    /** @var Collection<int, StudyGroup> */
    private Collection $studyGroups;

    /** @var Collection<int, Subject> */
    private Collection $subjects;

    /** @var Collection<int, User> */
    private Collection $teachers;

    /** @var array<int, array<int, array{slot:int,start:string,end:string,is_break:bool}>> */
    private array $masterDays = [];

    public function __construct(
        School $school,
        AcademicYear $academicYear,
        ?User $user = null,
        bool $updateExisting = true,
        bool $defaultActive = true,
    ) {
        $this->school = $school;
        $this->academicYear = $academicYear;
        $this->user = $user;
        $this->updateExisting = $updateExisting;
        $this->defaultActive = $defaultActive;

        $this->buildReferences();
    }

    /**
     * @return array{stats:array{created:int,updated:int,skipped:int},errors:array<int,string>,warnings:array<int,string>}
     */
    public function import(UploadedFile $file): array
    {
        Excel::import($this, $file);

        return [
            'stats' => $this->stats,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * Hanya proses sheet pertama (JADWAL); sheet referensi diabaikan.
     *
     * @return array<int, self>
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw new \RuntimeException('File tidak berisi baris data.');
        }

        $first = $rows->first();
        $firstRow = is_array($first) ? $first : ($first?->toArray() ?? []);
        $firstRow = $this->normalizeRowKeys($firstRow);
        $keys = array_keys($firstRow);
        $required = ['rombel', 'hari', 'jam_ke'];
        $missing = array_values(array_diff($required, $keys));

        if ($missing !== []) {
            throw new \RuntimeException('Format kolom tidak dikenali. Kolom wajib: Rombel, Hari, Jam Ke. Gunakan template import terbaru.');
        }

        if (! in_array('mapel', $keys, true) && ! in_array('kode_mapel', $keys, true)) {
            throw new \RuntimeException('Kolom Mapel atau Kode Mapel wajib ada.');
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = is_array($row) ? $row : $row->toArray();
            $data = $this->normalizeRowKeys($data);

            if ($this->isBlankRow($data)) {
                continue;
            }

            try {
                $this->processRow($data, $rowNumber);
            } catch (\Throwable $e) {
                $this->stats['skipped']++;
                $this->errors[] = "Baris {$rowNumber}: ".$e->getMessage();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function processRow(array $data, int $rowNumber): void
    {
        $rombel = trim((string) ($data['rombel'] ?? ''));
        $hari = trim((string) ($data['hari'] ?? ''));
        $jamKe = trim((string) ($data['jam_ke'] ?? ''));
        $kodeMapel = trim((string) ($data['kode_mapel'] ?? ''));
        $namaMapel = trim((string) ($data['mapel'] ?? ''));
        $guru = trim((string) ($data['guru'] ?? ''));
        $ruang = trim((string) ($data['ruang'] ?? ''));
        $catatan = trim((string) ($data['catatan'] ?? ''));

        if ($rombel === '') {
            throw new \RuntimeException('Rombel wajib diisi.');
        }

        if ($hari === '') {
            throw new \RuntimeException('Hari wajib diisi.');
        }

        if ($jamKe === '') {
            throw new \RuntimeException('Jam Ke wajib diisi.');
        }

        if ($kodeMapel === '' && $namaMapel === '') {
            throw new \RuntimeException('Kode Mapel atau Mapel wajib diisi.');
        }

        $studyGroup = $this->resolveStudyGroup($rombel);
        $day = $this->parseDay($hari);
        $slot = (int) $jamKe;

        if ($day === null) {
            throw new \RuntimeException('Hari tidak dikenali. Gunakan 1-6 atau nama hari (Senin-Sabtu).');
        }

        if ($slot < 1) {
            throw new \RuntimeException('Jam Ke harus berupa angka minimal 1.');
        }

        $slotMeta = collect($this->masterDays[$day] ?? [])->firstWhere('slot', $slot);

        if (! $slotMeta) {
            throw new \RuntimeException("Jam Ke {$slot} tidak ada pada master Jam Pelajaran hari {$this->dayName($day)}.");
        }

        if ((bool) ($slotMeta['is_break'] ?? false)) {
            throw new \RuntimeException("Jam Ke {$slot} pada hari {$this->dayName($day)} adalah jam istirahat.");
        }

        $subject = $this->resolveSubject($kodeMapel, $namaMapel);
        $teacher = $guru !== '' ? $this->resolveTeacher($guru) : null;

        if ($teacher) {
            $availability = app(TeacherAvailabilityService::class);
            if (! $availability->isAllowed((string) $teacher->id, $this->school->id, $day, $slot)) {
                $this->warnings[] = "Baris {$rowNumber}: guru {$teacher->name} tidak tersedia pada hari/jam ini menurut pengaturan ketersediaan.";
            }

            $teacherConflict = JadwalKbm::query()
                ->where('teacher_id', $teacher->id)
                ->where('day_of_week', $day)
                ->where('slot_index', $slot)
                ->where('academic_year_id', $this->academicYear->id)
                ->where('is_active', true)
                ->exists();

            if ($teacherConflict) {
                $this->warnings[] = "Baris {$rowNumber}: guru {$teacher->name} sudah mengajar pada hari/jam yang sama.";
            }
        }

        $existing = JadwalKbm::query()
            ->where('study_group_id', $studyGroup->id)
            ->where('day_of_week', $day)
            ->where('slot_index', $slot)
            ->first();

        if ($existing && ! $this->updateExisting) {
            throw new \RuntimeException("Jadwal {$studyGroup->full_name} hari {$this->dayName($day)} jam {$slot} sudah ada. Aktifkan 'Perbarui jadwal yang sudah ada'.");
        }

        $isActive = $this->parseBoolean($data['aktif'] ?? null, $this->defaultActive);

        $payload = [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id' => $studyGroup->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher?->id,
            'day_of_week' => $day,
            'slot_index' => $slot,
            'start_time' => $slotMeta['start'],
            'end_time' => $slotMeta['end'],
            'room' => $ruang !== '' ? $ruang : ($studyGroup->room ?? null),
            'is_active' => $isActive,
            'notes' => $catatan !== '' ? $catatan : null,
        ];

        if ($existing) {
            $existing->update($payload);
            $this->stats['updated']++;

            return;
        }

        JadwalKbm::create($payload);
        $this->stats['created']++;
    }

    private function buildReferences(): void
    {
        $this->studyGroups = StudyGroup::query()
            ->where('school_id', $this->school->id)
            ->where('academic_year_id', $this->academicYear->id)
            ->orderBy('name')
            ->get();

        $this->subjects = Subject::query()
            ->where(function ($q) {
                $q->whereNull('school_id')->orWhere('school_id', $this->school->id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $teacherIds = app(TeacherAvailabilityService::class)->teachersForSchool($this->school->id)->pluck('id');
        $this->teachers = User::query()
            ->whereIn('id', $teacherIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'username']);

        $this->masterDays = app(JadwalGeneratorService::class)->masterSlotsByDay($this->school->id);
    }

    private function resolveStudyGroup(string $value): StudyGroup
    {
        $needle = $this->normalize($value);

        $matches = $this->studyGroups->filter(function (StudyGroup $group) use ($needle) {
            return $this->normalize((string) $group->name) === $needle
                || $this->normalize((string) $group->code) === $needle;
        })->values();

        if ($matches->isEmpty()) {
            throw new \RuntimeException("Rombel '{$value}' tidak ditemukan pada tahun ajaran aktif.");
        }

        if ($matches->count() > 1) {
            throw new \RuntimeException("Rombel '{$value}' ambigu. Gunakan nama rombel yang lebih spesifik.");
        }

        return $matches->first();
    }

    private function resolveSubject(string $code, string $name): Subject
    {
        if ($code !== '') {
            $needle = $this->normalize($code);
            $matches = $this->subjects->filter(fn (Subject $subject) => $this->normalize((string) $subject->code) === $needle)->values();
            $resolved = $this->preferSchoolSubject($matches);

            if ($resolved) {
                return $resolved;
            }
        }

        if ($name !== '') {
            $needle = $this->normalize($name);
            $matches = $this->subjects->filter(fn (Subject $subject) => $this->normalize((string) $subject->name) === $needle)->values();
            $resolved = $this->preferSchoolSubject($matches);

            if ($resolved) {
                return $resolved;
            }

            if ($matches->count() > 1) {
                throw new \RuntimeException("Mapel '{$name}' ambigu. Isi Kode Mapel agar lebih spesifik.");
            }
        }

        $label = $code !== '' ? $code : $name;
        throw new \RuntimeException("Mapel '{$label}' tidak ditemukan di master mapel sekolah.");
    }

    /**
     * @param  Collection<int, Subject>  $matches
     */
    private function preferSchoolSubject(Collection $matches): ?Subject
    {
        if ($matches->isEmpty()) {
            return null;
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        $local = $matches->filter(fn (Subject $subject) => (string) $subject->school_id === (string) $this->school->id)->values();

        return $local->count() === 1 ? $local->first() : null;
    }

    private function resolveTeacher(string $value): User
    {
        $needle = $this->normalize($value);

        $matches = $this->teachers->filter(function (User $teacher) use ($needle) {
            return $this->normalize((string) $teacher->name) === $needle
                || $this->normalize((string) $teacher->email) === $needle
                || $this->normalize((string) $teacher->username) === $needle;
        })->values();

        if ($matches->isEmpty()) {
            throw new \RuntimeException("Guru '{$value}' tidak ditemukan pada data GTK sekolah ini.");
        }

        if ($matches->count() > 1) {
            throw new \RuntimeException("Guru '{$value}' ambigu. Gunakan email/username guru.");
        }

        return $matches->first();
    }

    private function parseDay(string $value): ?int
    {
        $value = $this->normalize($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $day = (int) $value;

            return $day >= 1 && $day <= 6 ? $day : null;
        }

        $value = preg_replace('/^hari\s+/', '', $value);

        return match ($value) {
            'senin', 'monday' => 1,
            'selasa', 'tuesday' => 2,
            'rabu', 'wednesday' => 3,
            'kamis', 'thursday' => 4,
            'jumat', 'jum\'at', 'friday' => 5,
            'sabtu', 'saturday' => 6,
            default => null,
        };
    }

    private function parseBoolean(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $value = $this->normalize((string) $value);

        return in_array($value, ['1', 'true', 'ya', 'y', 'yes', 'aktif', 'active'], true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeRowKeys(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $key = trim(str_replace("\xEF\xBB\xBF", '', (string) $key));
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function isBlankRow(array $data): bool
    {
        foreach (['rombel', 'hari', 'jam_ke', 'kode_mapel', 'mapel', 'guru'] as $key) {
            if (trim((string) ($data[$key] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalize(?string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value) ?? ''));
    }

    private function dayName(int $day): string
    {
        return match ($day) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => '-',
        };
    }
}
