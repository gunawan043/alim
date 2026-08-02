<?php

namespace App\Console\Commands\Uks;

use App\Models\Student;
use App\Models\StudentImmunization;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoGenerateImunisasiSchedule extends Command
{
    protected $signature = 'uks:auto-immunization {--date= : Process students up to this date (Y-m-d)}';

    protected $description = 'Auto-generate recommended immunization records from student birthdate';

    /**
     * Jadwal imunisasi dasar sesuai IKD (Imunisasi Kehamilan & Anak) Indonesia:
     * Key = identifier, value = required_age_days
     */
    public function handle()
    {
        $processDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        $this->info("Processing immunizations up to: {$processDate->format('d M Y')}");

        // Daftar imunisasi wajib anak (berdasarkan usia dalam hari)
        // Source: Kemenkes RI / IDAI - Jadwal Imunisasi Dasar Lengkap
        $recommendedSch = [
            // Bayi baru lahir (0-28 hari)
            ['immunization_type' => 'Hepatitis B',       'vaccine_name' => 'Hepatitis B-0',          'min_age_days' => 0,   'max_age_days' => 7],
            ['immunization_type' => 'BCG',                'vaccine_name' => 'BCG',                    'min_age_days' => 0,   'max_age_days' => 84],
            ['immunization_type' => 'Polio',              'vaccine_name' => 'Polio-0',                'min_age_days' => 0,   'max_age_days' => 14],

            // 2 bulan
            ['immunization_type' => 'DPT-HB-Hib',         'vaccine_name' => 'DPT-HB-Hib-1',           'min_age_days' => 56,  'max_age_days' => 91],
            ['immunization_type' => 'Polio',              'vaccine_name' => 'Polio-1',                'min_age_days' => 56,  'max_age_days' => 91],
            ['immunization_type' => 'Rotavirus',          'vaccine_name' => 'Rotavirus-1',             'min_age_days' => 56,  'max_age_days' => 91],

            // 3 bulan
            ['immunization_type' => 'DPT-HB-Hib',         'vaccine_name' => 'DPT-HB-Hib-2',           'min_age_days' => 84,  'max_age_days' => 119],
            ['immunization_type' => 'Polio',              'vaccine_name' => 'Polio-2',                'min_age_days' => 84,  'max_age_days' => 119],
            ['immunization_type' => 'Rotavirus',          'vaccine_name' => 'Rotavirus-2',             'min_age_days' => 84,  'max_age_days' => 119],

            // 4 bulan
            ['immunization_type' => 'DPT-HB-Hib',         'vaccine_name' => 'DPT-HB-Hib-3',           'min_age_days' => 112, 'max_age_days' => 147],
            ['immunization_type' => 'Polio',              'vaccine_name' => 'Polio-3',                'min_age_days' => 112, 'max_age_days' => 147],

            // 9 bulan
            ['immunization_type' => 'Campak/MP',          'vaccine_name' => 'Measles-1',              'min_age_days' => 266, 'max_age_days' => 322],

            // 12-15 bulan (Booster)
            ['immunization_type' => 'HBV',                'vaccine_name' => 'Hepatitis B-4',          'min_age_days' => 365, 'max_age_days' => 483],
            ['immunization_type' => 'PCV',                'vaccine_name' => 'Pneumococcal-1',          'min_age_days' => 365, 'max_age_days' => 483],
            ['immunization_type' => 'Varicella',          'vaccine_name' => 'Varicella-1',             'min_age_days' => 334, 'max_age_days' => 450],

            // 18 bulan
            ['immunization_type' => 'DPT-HB-Hib',         'vaccine_name' => 'DPT-HB-Hib Booster-1',   'min_age_days' => 525, 'max_age_days' => 600],
            ['immunization_type' => 'Polio',              'vaccine_name' => 'OPV Booster-1',          'min_age_days' => 525, 'max_age_days' => 600],

            // 5 tahun (SD)
            ['immunization_type' => 'DPT-HB-Hib',         'vaccine_name' => 'DPT-HB-Hib Booster-2',   'min_age_days' => 1575, 'max_age_days' => 1890],
            ['immunization_type' => 'Polio',              'vaccine_name' => 'OPV Booster-2',          'min_age_days' => 1575, 'max_age_days' => 1890],
            ['immunization_type' => 'Campak/MP',          'vaccine_name' => 'Measles-Rubella (MR)-1', 'min_age_days' => 1575, 'max_age_days' => 1890],

            // 7-12 tahun (SD kelas 1-6) — Td booster
            ['immunization_type' => 'Td (Difteri)',       'vaccine_name' => 'Td Booster-1',            'min_age_days' => 2190, 'max_age_days' => 4380],
            ['immunization_type' => 'HPV',                'vaccine_name' => 'HPV-1',                   'min_age_days' => 3285, 'max_age_days' => 4745],

            // Menstruation/Remaja (SMP/SMA)
            ['immunization_type' => 'HPV',                'vaccine_name' => 'HPV-2',                   'min_age_days' => 3356, 'max_age_days' => 5110],

            // Remaja umum
            ['immunization_type' => 'PCV',                'vaccine_name' => 'Pneumococcal Booster',    'min_age_days' => 3285, 'max_age_days' => 4745],
        ];

        $activeStatuses = ['aktif', 'lulus', 'transfer_keluar'];
        $totalProcessed = 0;
        $totalSkipped = 0;
        $totalCreated = 0;
        $totalError = 0;

        $query = Student::whereNotNull('birth_date')
            ->whereIn('status', $activeStatuses)
            ->orderBy('birth_date');

        if ($schoolId = \App\Services\SchoolContextService::getCurrentSchoolId()) {
            $query->where('school_id', $schoolId);
        }

        $students = $query->cursor();

        foreach ($students as $student) {
            $birthdate = Carbon::parse($student->birth_date);
            $ageDays = $birthdate->diffInDays($processDate, false); // signed = future minus past
            $ageDays = abs($ageDays); // always positive age

            if ($ageDays < 0 || $ageDays > 5110) { // younger than birth or older than 14 years → skip
                $totalSkipped++;

                continue;
            }

            foreach ($recommendedSch as $rec) {
                // Check if already recorded
                $exists = StudentImmunization::where('student_id', $student->id)
                    ->where('immunization_type', $rec['immunization_type'])
                    ->exists();

                if ($exists) {
                    $totalSkipped++;

                    continue;
                }

                // Check if the student's age falls within this immunization window AND is past the min_age
                if ($ageDays >= $rec['min_age_days'] && $ageDays <= ($rec['max_age_days'] + 14)) {
                    // Recommend but not auto-create — create "suggested" record
                    // We'll create with a flag so it shows in dashboard as "suggested"
                    $studentRec = new StudentImmunization;
                    $studentRec->student_id = $student->id;
                    $studentRec->immunization_type = $rec['immunization_type'];
                    $studentRec->vaccine_name = $rec['vaccine_name'];
                    $studentRec->suggested = true;
                    $studentRec->recommended_age_start = $rec['min_age_days'];
                    $studentRec->recommended_age_end = $rec['max_age_days'];

                    if ($student->school_id) {
                        $studentRec->school_id = $student->school_id;
                    }

                    try {
                        $studentRec->save();
                        $totalCreated++;
                    } catch (\Exception $e) {
                        $this->error("Failed for student #{$student->id}: ".$e->getMessage());
                        $totalError++;
                    }
                } else {
                    $totalSkipped++;
                }
            }

            $totalProcessed++;
        }

        // Summary output
        $this->newLine();
        $this->info('═══ Auto Immunization Summary ═══');
        $this->line("  Process Date      : {$processDate->format('d M Y')}");
        $this->line('  Students Scanned  : '.number_format($totalProcessed));
        $this->line('  Students Skipped  : '.number_format($totalSkipped));
        $this->line('  Records Created   : '.number_format($totalCreated));
        $this->line('  Errors            : '.number_format($totalError));
        $this->line('═══════════════════════════════════');

        return Command::SUCCESS;
    }
}
