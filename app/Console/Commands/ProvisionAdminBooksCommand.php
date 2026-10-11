<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionStudyGroupSubjectAcademicStructureJob;
use App\Models\TeachingAssignment;
use Illuminate\Console\Command;

/**
 * Sinkronkan buku nilai (teacher_admin_books), struktur mapel-rombel (study_group_subjects),
 * dan placeholder nilai dari seluruh penugasan mengajar yang sudah ada.
 *
 * Berguna untuk data lama yang dibuat SEBELUM observer penugasan aktif.
 */
class ProvisionAdminBooksCommand extends Command
{
    protected $signature = 'akademik:provision-books {--school= : Batasi ke satu sekolah (uuid)}';

    protected $description = 'Buat/sinkronkan buku nilai dari penugasan mengajar yang sudah ada (idempoten).';

    public function handle(): int
    {
        $query = TeachingAssignment::query()
            ->where('status', 'active')
            ->when($this->option('school'), fn ($q) => $q->where('school_id', $this->option('school')));

        $total = (clone $query)->count();
        $processed = 0;

        $this->info("Memproses {$total} penugasan aktif...");

        $bar = $this->output->createProgressBar($total);

        $query->orderBy('id')->chunk(50, function ($assignments) use (&$processed, $bar) {
            foreach ($assignments as $assignment) {
                ProvisionStudyGroupSubjectAcademicStructureJob::dispatchSync(
                    $assignment->id,
                    $assignment->study_group_id,
                    $assignment->subject_id,
                    $assignment->teacher_id,
                    $assignment->school_id,
                    $assignment->academic_year_id,
                    null,
                    'created',
                );

                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Selesai: {$processed} penugasan diproses.");

        return self::SUCCESS;
    }
}
