<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\LibrarySetting;
use App\Models\LibraryUnit;
use App\Models\School;
use Illuminate\Database\Seeder;

class LibraryUnitSeeder extends Seeder
{
    /**
     * Seed tiga unit perpustakaan wajib: Putra, Putri, dan SD.
     * Idempotent: kode yang sudah ada tidak dibuat ulang dan tidak ditimpa.
     */
    public function run(): void
    {
        $schools = School::query()->where('is_active', true)->orderBy('name')->get();

        $pickSchool = function (array $levels, ?string $gender = null) use ($schools): ?School {
            return $schools->first(function (School $school) use ($levels, $gender) {
                $level = strtolower((string) ($school->school_level ?? ''));
                $schoolGender = strtolower((string) ($school->school_gender ?? ''));

                return in_array($level, $levels, true)
                    && ($gender === null || $schoolGender === $gender);
            });
        };

        $putra = $pickSchool(['smp', 'sma'], 'putra') ?? $pickSchool(['smp', 'sma']);
        $putri = $pickSchool(['smp', 'sma'], 'putri') ?? $pickSchool(['smp', 'sma']);
        $sd = $pickSchool(['sd']);

        $definitions = [
            ['code' => 'PERPUS-PUTRA', 'name' => 'Perpustakaan Putra', 'school' => $putra, 'gender_scope' => 'L', 'level_scope' => 'sd_smp'],
            ['code' => 'PERPUS-PUTRI', 'name' => 'Perpustakaan Putri', 'school' => $putri, 'gender_scope' => 'P', 'level_scope' => 'sd_smp'],
            ['code' => 'PERPUS-SD', 'name' => 'Perpustakaan SD', 'school' => $sd, 'gender_scope' => 'mixed', 'level_scope' => 'sd'],
        ];

        $yearId = AcademicYear::query()->where('is_active', true)->value('id');

        foreach ($definitions as $definition) {
            $school = $definition['school'];

            $unit = LibraryUnit::query()->firstOrCreate(
                ['code' => $definition['code']],
                [
                    'work_unit_id' => $school?->work_unit_id,
                    'school_id' => $school?->id,
                    'name' => $definition['name'],
                    'gender_scope' => $definition['gender_scope'],
                    'level_scope' => $definition['level_scope'],
                    'is_active' => true,
                    'notes' => 'Unit default dari LibraryUnitSeeder. Sesuaikan melalui menu Unit & Pengaturan.',
                ]
            );

            if ($yearId) {
                LibrarySetting::query()->firstOrCreate(
                    ['library_unit_id' => $unit->id, 'academic_year_id' => $yearId],
                    [
                        'loan_days' => 7,
                        'max_active_loans' => 3,
                        'allow_renewal' => true,
                        'max_renewals' => 1,
                        'renewal_days' => 7,
                        'allow_fine' => false,
                        'fine_per_day' => 0,
                        'max_fine' => null,
                        'replacement_card_fee' => 0,
                    ]
                );
            }
        }
    }
}
