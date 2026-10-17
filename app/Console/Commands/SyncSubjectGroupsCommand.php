<?php

namespace App\Console\Commands;

use App\Models\Subject;
use App\Services\SubjectGroupResolver;
use Illuminate\Console\Command;

/**
 * Tahap 1 — backfill/sinkronisasi pemetaan mapel → rumpun.
 */
class SyncSubjectGroupsCommand extends Command
{
    protected $signature = 'alim:sync-subject-groups
        {--apply-suggestions : Terapkan saran pola sebagai pemetaan (aksi admin eksplisit)}
        {--force : Timpa pemetaan yang sudah ada (butuh --apply-suggestions)}';

    protected $description = 'Laporkan/petakan mapel ke rumpun. Otoritas tetap master UI; pola hanya saran.';

    public function handle(SubjectGroupResolver $resolver): int
    {
        $apply = (bool) $this->option('apply-suggestions');
        $force = (bool) $this->option('force');

        if (! $apply) {
            $unmapped = Subject::query()->whereNull('subject_group_id')->count();
            $this->info("Mapel belum dipetakan ke rumpun: {$unmapped}.");
            $this->line('Gunakan --apply-suggestions untuk menerapkan saran pola sebagai pemetaan master.');

            return self::SUCCESS;
        }

        $query = Subject::query();

        if (! $force) {
            $query->whereNull('subject_group_id');
        }

        $updated = 0;
        $skipped = 0;

        $query->orderBy('name')->chunkById(200, function ($subjects) use ($resolver, $force, &$updated, &$skipped) {
            foreach ($subjects as $subject) {
                $before = $subject->subject_group_id;
                $resolver->applySuggestion($subject, $force);

                if ($subject->subject_group_id !== $before) {
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Selesai: {$updated} mapel dipetakan (saran), {$skipped} tidak berubah.");

        return self::SUCCESS;
    }

}
