<?php

namespace App\Console\Commands;

use App\Models\DormitoryAttendance;
use App\Models\DormitoryAttendanceRecap;
use App\Models\DormitoryInventory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoomMove;
use App\Models\DormitoryViolation;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentMahrom;
use App\Models\StudyGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteSantriData extends Command
{
    protected $signature = 'delete:santri-data {--force : Skip confirmation}';

    protected $description = 'Hapus semua data rombel (study_groups) dan santri (students) beserta data terkait';

    public function handle(): int
    {
        $force = $this->option('force');

        // ── Count before ──────────────────────────────────────────────────────
        $counts = [
            'student_class_histories' => StudentClassHistory::count(),
            'student_mahroms' => StudentMahrom::count(),
            'dormitory_residents' => DormitoryResident::count(),
            'dormitory_attendances' => DormitoryAttendance::count(),
            'dormitory_attendance_recaps' => DormitoryAttendanceRecap::count(),
            'dormitory_permits' => DormitoryPermit::count(),
            'dormitory_violations' => DormitoryViolation::count(),
            'dormitory_room_moves' => DormitoryRoomMove::count(),
            'dormitory_inventories' => DormitoryInventory::count(),
            'students' => Student::count(),
            'study_groups' => StudyGroup::count(),
        ];

        $totalRelated = $counts['student_class_histories']
            + $counts['student_mahroms']
            + $counts['dormitory_residents']
            + $counts['dormitory_attendances']
            + $counts['dormitory_attendance_recaps']
            + $counts['dormitory_permits']
            + $counts['dormitory_violations']
            + $counts['dormitory_room_moves']
            + $counts['dormitory_inventories'];

        $this->line('───────────────────────────────────────────');
        $this->line('Data yang akan dihapus:');
        $this->line('───────────────────────────────────────────');
        foreach ($counts as $table => $count) {
            $this->line("  {$table}: {$count}");
        }
        $this->line('───────────────────────────────────────────');
        $this->line('Total semua record: '.($totalRelated + $counts['students'] + $counts['study_groups']));
        $this->line('───────────────���───────────────────────────');

        if (! $force) {
            $confirm = $this->confirm('Yakin ingin menghapus SEMUA data ini? [yes/no]');
            if (! $confirm) {
                $this->info('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Delete in order: related → students → study_groups
        $deleted = 0;

        $deleted += StudentClassHistory::count();
        StudentClassHistory::truncate();

        $deleted += StudentMahrom::count();
        StudentMahrom::truncate();

        $deleted += DormitoryInventory::count();
        DormitoryInventory::truncate();

        $deleted += DormitoryViolation::count();
        DormitoryViolation::truncate();

        $deleted += DormitoryPermit::count();
        DormitoryPermit::truncate();

        $deleted += DormitoryRoomMove::count();
        DormitoryRoomMove::truncate();

        $deleted += DormitoryResident::count();
        DormitoryResident::truncate();

        $deleted += DormitoryAttendance::count();
        DormitoryAttendance::truncate();

        $deleted += DormitoryAttendanceRecap::count();
        DormitoryAttendanceRecap::truncate();

        $deleted += Student::count();
        Student::truncate();

        $deleted += StudyGroup::count();
        StudyGroup::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info("✅ Semua data rombel & santri berhasil dihapus ({$deleted} record).");

        return self::SUCCESS;
    }
}
