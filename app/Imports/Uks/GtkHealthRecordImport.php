<?php

namespace App\Imports\Uks;

use App\Models\GtkEmployment;
use App\Models\GtkHealthRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GtkHealthRecordImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = $row->toArray();

                if (blank(trim((string) ($data['nupy'] ?? ''))) && blank(trim((string) ($data['email'] ?? '')))) {
                    $this->skipped++;

                    continue;
                }

                try {
                    $user = $this->resolveUser($data);

                    if (! $user) {
                        throw new \RuntimeException('GTK dengan NUPY/email tersebut tidak ditemukan.');
                    }

                    $date = $this->date($data['tanggal_pemeriksaan'] ?? null);
                    if (! $date) {
                        throw new \RuntimeException('Tanggal pemeriksaan tidak valid.');
                    }

                    $height = $this->number($data['tinggi_cm'] ?? null);
                    $weight = $this->number($data['berat_kg'] ?? null);
                    $systolic = $this->number($data['sistolik'] ?? null);
                    $diastolic = $this->number($data['diastolik'] ?? null);

                    if ($height === null || $weight === null) {
                        throw new \RuntimeException('Tinggi dan berat badan harus berupa angka.');
                    }

                    $record = GtkHealthRecord::query()
                        ->where('user_id', $user->id)
                        ->whereDate('check_date', $date->toDateString())
                        ->first();

                    $bloodPressure = $systolic !== null && $diastolic !== null
                        ? ((int) $systolic).'/'.((int) $diastolic)
                        : trim((string) ($data['tekanan_darah'] ?? ''));

                    $payload = [
                        'user_id' => $user->id,
                        'check_date' => $date->toDateString(),
                        'height' => $height,
                        'weight' => $weight,
                        'pulse' => $this->integer($data['nadi'] ?? null),
                        'temperature' => $this->number($data['suhu'] ?? null),
                        'respiration_rate' => $this->integer($data['respirasi'] ?? null),
                        'oxygen_saturation' => $this->integer($data['saturasi_oksigen'] ?? null),
                        'blood_pressure' => $bloodPressure !== '' ? $bloodPressure : null,
                        'blood_sugar_fasting' => $this->number($data['gula_darah'] ?? null),
                        'cholesterol_total' => $this->number($data['kolesterol'] ?? null),
                        'uric_acid' => $this->number($data['asam_urat'] ?? null),
                        'hemoglobin' => $this->number($data['hemoglobin'] ?? null),
                        'notes' => trim((string) ($data['catatan'] ?? '')) ?: null,
                        'recorded_by' => auth()->id(),
                        'source' => 'import',
                    ];

                    if ($record) {
                        $record->update($payload);
                        $this->updated++;
                    } else {
                        GtkHealthRecord::create($payload);
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

    private function resolveUser(array $data): ?User
    {
        $nupy = trim((string) ($data['nupy'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if ($nupy !== '') {
            $employment = GtkEmployment::where('nupy', $nupy)->first();
            if ($employment?->user) {
                return $employment->user;
            }
        }

        return $email !== '' ? User::where('email', $email)->first() : null;
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function integer(mixed $value): ?int
    {
        return $this->number($value) === null ? null : (int) round($this->number($value));
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
