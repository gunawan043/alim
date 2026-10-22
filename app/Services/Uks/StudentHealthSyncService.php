<?php

namespace App\Services\Uks;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentHealthMetric;
use App\Models\StudentHealthRecord;

/**
 * Menyinkronkan data antropometri santri (tinggi, berat, lingkar kepala)
 * ke data kesehatan UKS. Dipakai oleh import santri dan form santri agar
 * data yang sudah diisi tidak hanya tersimpan di tabel students.
 */
class StudentHealthSyncService
{
    /**
     * @param  array{height?: mixed, weight?: mixed, head_circumference?: mixed}  $measurements
     */
    public function sync(
        Student $student,
        array $measurements = [],
        ?string $academicYearId = null,
        ?string $recordDate = null,
    ): void {
        $height = $this->positiveInt($measurements['height'] ?? $student->height);
        $weight = $this->positiveInt($measurements['weight'] ?? $student->weight);
        $head = $this->positiveInt($measurements['head_circumference'] ?? $student->head_circumference);

        if ($height === null && $weight === null && $head === null) {
            return;
        }

        // Simpan nilai terbaru di tabel santri agar form santri tetap sinkron.
        $studentUpdates = array_filter([
            'height' => $height,
            'weight' => $weight,
            'head_circumference' => $head,
        ], fn ($value) => $value !== null);

        if ($studentUpdates !== []) {
            $student->forceFill($studentUpdates)->save();
        }

        $record = StudentHealthRecord::firstOrNew(['student_id' => $student->id]);
        $record->school_id = $student->school_id;

        if ($height !== null) {
            $record->height_cm = $height;
        }
        if ($weight !== null) {
            $record->weight_kg = $weight;
        }
        if ($head !== null) {
            $record->head_circumference_cm = $head;
        }
        if ($record->height_cm && $record->weight_kg) {
            $heightMeter = $record->height_cm / 100;
            $record->bmi = round($record->weight_kg / ($heightMeter * $heightMeter), 2);
        }

        $record->save();

        $academicYearId ??= AcademicYear::query()->where('is_active', true)->value('id');

        if (! $academicYearId) {
            return;
        }

        $metric = StudentHealthMetric::firstOrNew([
            'student_id' => $student->id,
            'academic_year_id' => $academicYearId,
            'record_date' => $recordDate ?: now()->toDateString(),
        ]);

        $metric->school_id = $student->school_id;
        if ($height !== null) {
            $metric->height_cm = $height;
        }
        if ($weight !== null) {
            $metric->weight_kg = $weight;
        }
        if ($head !== null) {
            $metric->head_circumference_cm = $head;
        }
        $metric->measurement_session = 'medical_checkup';
        $metric->notes = 'Disinkronkan dari data santri.';
        $metric->save();

        // Jangan biarkan sinkronisasi UKS menimpa data santri dengan nilai
        // lama; gunakan nilai metric terbaru yang baru saja disimpan.
        if ($metric->height_cm && $metric->weight_kg) {
            $metric->syncHeightWeightToStudent();
        }
    }

    private function positiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (int) round((float) str_replace(',', '.', (string) $value));

        return $number > 0 ? $number : null;
    }
}
