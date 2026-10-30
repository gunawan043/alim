<?php

namespace App\Imports\Uks;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentHealthMetric;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HealthMetricImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    private ?string $academicYearId;

    public function __construct(private readonly ?string $schoolId = null)
    {
        $this->academicYearId = AcademicYear::where('is_active', true)->value('id');
    }

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = $row->toArray();

                if (blank(trim((string) ($data['nisn'] ?? ''))) && blank(trim((string) ($data['nis'] ?? '')))) {
                    $this->skipped++;

                    continue;
                }

                try {
                    $nisn = trim((string) ($data['nisn'] ?? ''));
                    $nis = trim((string) ($data['nis'] ?? ''));

                    $student = Student::query()
                        ->when($this->schoolId, fn ($q) => $q->where('school_id', $this->schoolId))
                        ->where(function ($q) use ($nisn, $nis) {
                            if ($nisn !== '') {
                                $q->where('nisn', $nisn);
                            }
                            if ($nis !== '') {
                                $q->orWhere('nis', $nis);
                            }
                        })
                        ->first();

                    if (! $student) {
                        throw new \RuntimeException('Santri dengan NISN/NIS tersebut tidak ditemukan.');
                    }

                    $date = $this->date($data['tanggal_pengukuran'] ?? null);
                    if (! $date) {
                        throw new \RuntimeException('Tanggal pengukuran tidak valid.');
                    }

                    $height = $this->number($data['tinggi_cm'] ?? null);
                    $weight = $this->number($data['berat_kg'] ?? null);
                    $head = $this->number($data['lingkar_kepala_cm'] ?? null);

                    if ($height === null || $weight === null) {
                        throw new \RuntimeException('Tinggi dan berat badan harus berupa angka.');
                    }

                    $session = trim((string) ($data['sesi'] ?? 'lainnya')) ?: 'lainnya';

                    $metric = StudentHealthMetric::query()
                        ->where('student_id', $student->id)
                        ->whereDate('record_date', $date->toDateString())
                        ->where('measurement_session', $session)
                        ->first();

                    $payload = [
                        'student_id' => $student->id,
                        'school_id' => $student->school_id,
                        'academic_year_id' => $this->academicYearId,
                        'record_date' => $date->toDateString(),
                        'height_cm' => (int) round($height),
                        'weight_kg' => (int) round($weight),
                        'head_circumference_cm' => $head === null ? null : (int) round($head),
                        'measurement_session' => $session,
                        'measured_by' => auth()->id(),
                        'notes' => trim((string) ($data['catatan'] ?? '')) ?: null,
                    ];

                    if ($metric) {
                        $metric->update($payload);
                        $this->updated++;
                    } else {
                        StudentHealthMetric::create($payload);
                        $this->created++;
                    }
                } catch (\Throwable $e) {
                    $this->skipped++;
                    $this->errors[] = "Baris {$rowNumber}: ".$e->getMessage();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = 'Import dibatalkan: '.$e->getMessage();
        }
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
