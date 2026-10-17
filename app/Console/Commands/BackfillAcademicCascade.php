<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionStudentAcademicDataJob;
use App\Models\StudentClassHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

/**
 * Backfill command untuk siswa existing yang sudah ada sebelum
 * cascade provisioning dipasang.
 *
 * Tiga mode scope:
 *   --all                          : semua student_class_histories aktif
 *   --study-group=<uuid>           : satu rombel
 *   --academic-year=<uuid>         : satu tahun ajaran
 *   --student=<uuid>               : satu siswa
 *
 * Dispatch ke queue 'academic-provision' dalam Bus::batch supaya:
 *   1. Tidak membebani worker (1 batch = 1 transaksi monitoring)
 *   2. Ada progress feedback di Horizon / queue dashboard
 *   3. Bisa di-cancel per batch via `php artisan queue:prune-batches`
 *
 * Idempotency: job ProvisionStudentAcademicDataJob aman dipanggil
 * berulang kali — student_absences & raport_registrations di-skip
 * jika sudah ada untuk tuple (student, rombel, TA, semester).
 */
class BackfillAcademicCascade extends Command
{
    protected $signature = 'academic:backfill-cascade
                            {--all : Backfill semua student_class_histories aktif}
                            {--study-group= : Filter per rombel (UUID)}
                            {--academic-year= : Filter per tahun ajaran (UUID)}
                            {--student= : Filter per siswa (UUID)}
                            {--chunk=100 : Jumlah siswa per chunk ke dalam Bus::batch}
                            {--dry-run : Hanya hitung, tidak dispatch}';

    protected $description = 'Backfill student_absences, raport_registrations, dan admin_nilai_sumatif placeholder untuk siswa existing yang belum ter-provision.';

    public function handle(): int
    {
        $query = StudentClassHistory::query()->where('is_active', true);

        if ($student = $this->option('student')) {
            $query->where('student_id', $student);
        }
        if ($sg = $this->option('study-group')) {
            $query->where('study_group_id', $sg);
        }
        if ($ay = $this->option('academic-year')) {
            $query->where('academic_year_id', $ay);
        }

        if (! $this->option('all') && ! $this->option('student') && ! $this->option('study-group') && ! $this->option('academic-year')) {
            $this->error('Pilih minimal satu filter: --all, --student, --study-group, atau --academic-year');

            return self::FAILURE;
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Tidak ada student_class_history yang cocok dengan filter.');

            return self::SUCCESS;
        }

        $this->info("Ditemukan {$total} student_class_history aktif.");

        if ($this->option('dry-run')) {
            $this->warn('--dry-run aktif: tidak ada job yang di-dispatch.');
            $this->table(
                ['Student ID', 'Study Group ID', 'Academic Year ID', 'Join Date'],
                $query->get(['student_id', 'study_group_id', 'academic_year_id', 'join_date'])
                    ->take(20)
                    ->toArray(),
            );

            return self::SUCCESS;
        }

        $chunkSize = (int) $this->option('chunk');
        $dispatched = 0;
        $batchCount = 0;

        $query->orderBy('id')
            ->chunk($chunkSize, function ($histories) use (&$dispatched, &$batchCount) {
                $jobs = $histories->map(function (StudentClassHistory $h) {
                    $job = new ProvisionStudentAcademicDataJob(
                        (string) $h->student_id,
                        (string) $h->study_group_id,
                        (string) $h->academic_year_id,
                        (string) ($h->join_date ?? $h->created_at?->toDateString() ?? now()->toDateString()),
                    );
                    $job->onQueue('academic-provision');

                    return $job;
                })->all();

                Bus::batch($jobs)
                    ->name('backfill-academic-cascade')
                    ->onQueue('academic-provision')
                    ->allowFailures()
                    ->dispatch();

                $dispatched += count($jobs);
                $batchCount++;

                $this->getOutput()->writeln(sprintf(
                    '  batch #%d dispatched (%d job). Total: %d',
                    $batchCount,
                    count($jobs),
                    $dispatched,
                ));
            });

        $this->info("Selesai. {$dispatched} job di-dispatch dalam {$batchCount} batch.");

        return self::SUCCESS;
    }
}
