<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class StudentImport implements ToCollection
{
    private array  $errors        = [];
    private int    $successCount  = 0;
    private array  $duplicates    = [];
    private ?string $schoolId;
    private ?string $academicYearId;

    /** Fallback: rombel from form dropdown (overrides file column) */
    private ?string $overrideStudyGroupId;

    /** Cache: [school_id][normalized_rombel_name] => study_group_id */
    private array  $rombelCache   = [];

    // Kolom Excel (0-indexed) tanpa rombel:
    //  0=No  1=Nama  2=NIPD  3=JK  4=NISN  5=Tempat Lahir  6=Tanggal Lahir
    //  7=NIK  8=Agama  9=Alamat  10=RT  11=RW  12=Dusun  13=Kelurahan
    // 14=Kecamatan  15=Kode Pos  16=Jenis Tinggal  17=Alat Transportasi
    // 18=Telepon  19=HP  20=E-Mail  21=SKHUN  22=Penerima KPS  23=No. KPS
    // 24=Nama Ayah  25=Thn Lahir  26=Pendidikan  27=Pekerjaan  28=Penghasilan  29=NIK
    // 30=Nama Ibu  31=Thn Lahir  32=Pendidikan  33=Pekerjaan  34=Penghasilan  35=NIK
    // 36=Nama Wali  37=Thn Lahir  38=Pendidikan  39=Pekerjaan  40=Penghasilan  41=NIK
    // 42=No Peserta Ujian  43=No Seri Ijazah  44=Penerima KIP
    // 45=Nomor KIP  46=Nama di KIP  47=Nomor KKS  48=No Registrasi Akta Lahir
    // 49=Bank  50=Nomor Rekening  51=Rekening Atas Nama  52=Layak PIP  53=Alasan PIP
    // 54=Kebutuhan Khusus  55=Sekolah Asal  56=Anak ke-berapa
    // 57=Lintang  58=Bujur  59=No KK
    // 60=Berat Badan  61=Tinggi Badan  62=Lingkar Kepala
    // 63=Jml Saudara Kandung  64=Jarak ke Sekolah (KM)

    public function __construct(?string $schoolId = null, ?string $overrideStudyGroupId = null)
    {
        $this->schoolId = $schoolId;
        $this->overrideStudyGroupId = $overrideStudyGroupId;
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->academicYearId = $activeYear?->id;
    }

    /**
     * Scan header rows to find the "Rombel Saat Ini" column index.
     * Accepts: "Rombel Saat Ini", "Rombel", "Kelas", "Rombongan Belajar"
     */
    private function findRombelColumnIndex(Collection $rows): int
    {
        foreach ([3, 4, 5] as $idx) {
            if ($idx >= $rows->count()) continue;
            $row = $rows->get($idx);
            if (!$row) continue;

            foreach ($row as $colIdx => $cell) {
                if ($cell === null) continue;
                $header = strtolower(trim((string) $cell));

                if (in_array($header, ['rombel saat ini', 'rombel', 'rombongan belajar', 'kelas'])) {
                    Log::info('[StudentImport] Rombel column found at col ' . $colIdx . ' ("' . $cell . '")');
                    return (int) $colIdx;
                }
            }
        }
        return -1;
    }

    /**
     * Normalize a raw rombel cell value to DB format.
     * "Kelas 6C"  → "VI-C"
     * "6C"        → "VI-C"
     * "VI C"      → "VI-C"
     */
    private function normalizeRombelName(string $raw): string
    {
        // Strip "Kelas " prefix
        $s = preg_replace('/^Kelas\s+/i', '', trim($raw));
        // Remove all spaces
        $s = preg_replace('/\s+/', '', $s);
        $s = strtoupper($s);

        // Map "6C" style to roman numeral "VI-C"
        static $romanMap = [
            '1' => 'I',  '2' => 'II',  '3' => 'III',
            '4' => 'IV', '5' => 'V',   '6' => 'VI',
            '7' => 'VII','8' => 'VIII','9' => 'IX',
            '10' => 'X', '11' => 'XI', '12' => 'XII',
        ];

        // Try to match e.g. "6C" → extract "6" and "C"
        if (preg_match('/^(\d+)([A-Z])$/', $s, $m)) {
            $roman = $romanMap[$m[1]] ?? $m[1];
            return $roman . '-' . $m[2];
        }

        return $s;
    }

    /**
     * Pre-build rombel name → study_group_id cache for the active academic year.
     */
    private function buildRombelCache(): void
    {
        if (!$this->academicYearId) return;

        $studyGroups = StudyGroup::where('academic_year_id', $this->academicYearId)->get();
        foreach ($studyGroups as $sg) {
            // Exact name match
            $this->rombelCache[$sg->school_id][$sg->name] = $sg->id;
            // Normalized match
            $normalized = $this->normalizeRombelName($sg->name);
            $this->rombelCache[$sg->school_id][$normalized] = $sg->id;
        }
    }

    /**
     * Resolve study_group_id for a given row.
     * Priority: 1) from "Rombel Saat Ini" column  2) fallback dropdown override
     */
    private function resolveStudyGroupId(Collection $row, int $rombelColIdx): ?string
    {
        if ($rombelColIdx >= 0) {
            $raw = $this->cell($row, $rombelColIdx);
            if ($raw !== '') {
                $normalized = $this->normalizeRombelName($raw);

                // First, try exact school match if schoolId is known
                if ($this->schoolId && isset($this->rombelCache[$this->schoolId][$normalized])) {
                    Log::debug("[StudentImport] Rombel '{$raw}' → sg={$this->rombelCache[$this->schoolId][$normalized]}");
                    return $this->rombelCache[$this->schoolId][$normalized];
                }

                // Fallback: search all schools
                foreach ($this->rombelCache as $schoolId => $map) {
                    if (isset($map[$normalized])) {
                        Log::debug("[StudentImport] Rombel '{$raw}' (school={$schoolId}) → sg={$map[$normalized]}");
                        return $map[$normalized];
                    }
                }

                $this->errors[] = "Rombel tidak ditemukan di database: '{$raw}' — pastikan rombel sudah dibuat di menu rombel.";
            }
        }

        return $this->overrideStudyGroupId;
    }

    public function collection(Collection $rows): void
    {
        Log::info('[StudentImport] collection() — total rows: ' . $rows->count());

        // ── Pre-scan: detect rombel column + build lookup cache ─────────────
        $rombelColIdx = $this->findRombelColumnIndex($rows);
        $this->buildRombelCache();

        $activeYearId = $this->academicYearId;
        $overrideSgId = $this->overrideStudyGroupId;

        foreach ($rows as $idx => $row) {
            if ($idx < 5) continue;

            $nama = $this->cell($row, 1);
            if ($nama === '') continue;

            $nisn = $this->cell($row, 4);
            $nik  = $this->cell($row, 7);

            // Duplicate check
            if ($nisn || $nik) {
                $query = Student::query();
                if ($nisn) $query->orWhere('nisn', $nisn);
                if ($nik)  $query->orWhere('nik', $nik);
                $existing = $query->first();

                if ($existing) {
                    $this->duplicates[] = [
                        'nisn'    => $nisn ?: '-',
                        'nik'     => $nik  ?: '-',
                        'nama'    => $existing->name,
                        'sekolah' => $existing->school?->name ?? '-',
                        'catatan' => 'Sudah ada — dilewati',
                    ];
                    continue;
                }
            }

            $data = $this->parseRow($row);

            try {
                $student = Student::create($data);
                $this->successCount++;

                // Resolve rombel per row
                $sgId = $this->resolveStudyGroupId($row, $rombelColIdx);

                if ($sgId && $activeYearId) {
                    $currentCount = StudentClassHistory::where('study_group_id', $sgId)
                        ->where('is_active', true)
                        ->count();
                    $studyGroup = StudyGroup::find($sgId);
                    $capacity = $studyGroup?->capacity ?? 0;

                    if ($currentCount >= $capacity) {
                        $this->errors[] = "Baris " . ($idx + 1) . " ({$nama}): {$student->nisn} — kapasitas {$studyGroup?->name} penuh ({$currentCount}/{$capacity})";
                    } else {
                        StudentClassHistory::updateOrCreate(
                            [
                                'student_id'       => $student->id,
                                'study_group_id'   => $sgId,
                                'academic_year_id' => $activeYearId,
                            ],
                            [
                                'is_active' => true,
                                'join_date'  => now()->toDateString(),
                            ]
                        );
                    }
                } elseif (!$rombelColIdx && !$overrideSgId && $this->schoolId) {
                    // No rombel info at all
                    Log::warning("[StudentImport] Baris " . ($idx + 1) . " ({$nama}): tidak ada info rombel");
                }
            } catch (\Throwable $e) {
                $code = $e->getCode();
                if ($code === '01000' || str_contains($e->getMessage(), 'Data truncated')) {
                    $this->successCount++;
                } else {
                    $this->errors[] = "Baris " . ($idx + 1) . " ({$nama}): " . $e->getMessage();
                }
            }
        }

        Log::info('[StudentImport] DONE: success=' . $this->successCount
            . ' errors=' . count($this->errors)
            . ' duplicates=' . count($this->duplicates));
    }

    private function cell(Collection $row, int $index): string
    {
        $val = $row->get($index);
        return is_null($val) ? '' : trim((string) $val);
    }

    private function parseRow(Collection $row): array
    {
        $v = fn(int $i) => $this->cell($row, $i);

        return array_filter([
            'school_id'                => $this->schoolId,
            'name'                     => $v(1) ?: null,
            'nis'                      => $v(2) ?: null,
            'nisn'                     => $v(4) ?: null,
            'gender'                   => $this->normalizeGender($v(3)),
            'nik'                      => $v(7) ?: null,
            'birth_place'              => $v(5) ?: null,
            'birth_date'               => $this->parseDate($v(6)),
            'religion'                 => $v(8) ?: 'Islam',
            'address'                  => $v(9) ?: null,
            'rt'                       => $v(10) ?: null,
            'rw'                       => $v(11) ?: null,
            'hamlet'                   => $v(12) ?: null,
            'postal_code'              => $v(15) ?: null,
            'phone'                    => $v(18) ?: null,
            'mobile_phone'             => $v(19) ?: null,
            'email'                    => $v(20) ?: null,
            'residence_type'           => $this->mapResidenceType($v(16)),
            'transportation'           => $this->mapTransportation($v(17)),
            'skhun'                    => $v(21) ?: null,
            'latitude'                 => $this->parseDecimal($v(57)),
            'longitude'                => $this->parseDecimal($v(58)),
            'no_kk'                    => $v(59) ?: null,
            'distance_to_school'       => $this->parseDecimal($v(64)),
            'is_kps_receiver'          => $this->mapBool($v(22)),
            'kps_number'               => $v(23) ?: null,
            'father_name'             => $v(24) ?: null,
            'father_birth_year'        => $this->parseInt($v(25)),
            'father_education'         => $v(26) ?: null,
            'father_occupation'        => $v(27) ?: null,
            'father_income'            => $this->parseDecimal($v(28)),
            'father_nik'               => $v(29) ?: null,
            'mother_name'              => $v(30) ?: null,
            'mother_birth_year'        => $this->parseInt($v(31)),
            'mother_education'         => $v(32) ?: null,
            'mother_occupation'        => $v(33) ?: null,
            'mother_income'            => $this->parseDecimal($v(34)),
            'mother_nik'               => $v(35) ?: null,
            'guardian_name'            => $v(36) ?: null,
            'guardian_birth_year'      => $this->parseInt($v(37)),
            'guardian_education'       => $v(38) ?: null,
            'guardian_occupation'      => $v(39) ?: null,
            'guardian_income'          => $this->parseDecimal($v(40)),
            'guardian_nik'            => $v(41) ?: null,
            'ujian_national_number'    => $v(42) ?: null,
            'certificate_number'       => $v(43) ?: null,
            'is_kip_receiver'          => $this->mapBool($v(44)),
            'kip_number'              => $v(45) ?: null,
            'kip_name'                => $v(46) ?: null,
            'kks_number'              => $v(47) ?: null,
            'birth_certificate_number' => $v(48) ?: null,
            'bank_name'                => $v(49) ?: null,
            'bank_account_number'      => $v(50) ?: null,
            'bank_account_name'        => $v(51) ?: null,
            'is_pip_eligible'          => $this->mapBool($v(52)),
            'pip_reason'               => $v(53) ?: null,
            'special_needs'            => $this->mapSpecialNeeds($v(54)),
            'previous_school'          => $v(55) ?: null,
            'child_number'             => $this->parseInt($v(56)),
            'weight'                  => $this->parseDecimal($v(60)),
            'height'                  => $this->parseDecimal($v(61)),
            'head_circumference'       => $this->parseDecimal($v(62)),
            'sibling_count'           => $this->parseInt($v(63)),
            'status'                  => 'active',
        ], fn($val) => $val !== null && $val !== '');
    }

    private function normalizeGender(?string $v): ?string
    {
        if (!$v) return null;
        $v = strtoupper(trim($v));
        return in_array($v, ['L', 'P']) ? $v : null;
    }

    private function parseDate(?string $v): ?string
    {
        if (!$v) return null;
        try {
            return \Carbon\Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable) {
            try {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private function parseDecimal(mixed $v): ?float
    {
        if ($v === null || $v === '') return null;
        $v = str_replace(',', '.', (string) $v);
        $v = preg_replace('/[^\d.]/', '', $v);
        return $v === '' ? null : (float) $v;
    }

    private function parseInt(mixed $v): ?int
    {
        if ($v === null || $v === '') return null;
        $v = preg_replace('/[^\d]/', '', (string) $v);
        if ($v === '' || $v === '0') return null;
        return (int) $v;
    }

    private function mapBool(?string $v): ?bool
    {
        if (!$v) return null;
        $v = strtolower(trim((string) $v));
        if (in_array($v, ['1', 'ya', 'yes', 'true']))  return true;
        if (in_array($v, ['0', 'tidak', 'no', 'false'])) return false;
        return null;
    }

    private function mapSpecialNeeds(?string $v): ?string
    {
        if (!$v) return null;
        $v = strtolower(trim($v));
        $map = [
            'tidak ada'    => 'tidak', 'tidak' => 'tidak',
            'tuna daksa'   => 'fisik', 'tuna rungu' => 'fisik',
            'tuna netra'   => 'fisik', 'tuna grahita' => 'intelektual',
            'tuna laras'   => 'mental', 'down syndrome' => 'fisik',
            'autis'        => 'mental', 'hiperaktif' => 'mental',
        ];
        return $map[$v] ?? match ($v) {
            'fisik', 'intelektual', 'mental', 'sosial' => $v,
            default => 'tidak',
        };
    }

    private function mapResidenceType(?string $v): ?string
    {
        if (!$v) return null;
        $v = strtolower(trim($v));
        $map = [
            'bersama orang tua' => 'milik_orangtua',
            'milik sendiri'      => 'milik_orangtua',
            'kontrak / sewa'     => 'sewa',
            'sewa'              => 'sewa',
            'kos'               => 'sewa',
            'asrama'            => 'asrama',
            'pesantren'          => 'asrama',
            'panti'              => 'panti',
            'lainnya'            => 'lainnya',
        ];
        return $map[$v] ?? match ($v) {
            'milik_orangtua', 'sewa', 'asrama', 'panti', 'lainnya' => $v,
            default => 'lainnya',
        };
    }

    private function mapTransportation(?string $v): ?string
    {
        if (!$v) return null;
        $v = strtolower(trim($v));
        $map = [
            'jalan kaki'                                   => 'jalan_kaki',
            'sepeda'                                      => 'sepeda',
            'motor'                                        => 'motor',
            'mobil'                                        => 'mobil',
            'mobil pribadi'                                => 'mobil',
            'antar jemput'                                  => 'antar_jemput',
            'antar jemput sekolah'                         => 'antar_jemput',
            'mobil/bus antar jemput'                        => 'antar_jemput',
            'angkot / mikrolet'                            => 'angkutan_umum',
            'angkot'                                       => 'angkutan_umum',
            'bus'                                          => 'angkutan_umum',
            'transportasi umum'                             => 'angkutan_umum',
            'andong/bendi/sado/dokar/delman/becak'         => 'angkutan_umum',
        ];
        return $map[$v] ?? match ($v) {
            'jalan_kaki', 'sepeda', 'motor', 'mobil',
            'angkutan_umum', 'antar_jemput' => $v,
            default => 'lainnya',
        };
    }

    public function getErrors(): array      { return $this->errors; }
    public function getDuplicates(): array   { return $this->duplicates; }
    public function getSuccessCount(): int   { return $this->successCount; }
}