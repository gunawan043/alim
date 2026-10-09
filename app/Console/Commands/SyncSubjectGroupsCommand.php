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
    protected $signature = 'alim:sync-subject-groups {--force : Timpa pemetaan yang sudah ada}';

    protected $description = 'Sinkronkan subject_group_id pada mapel berdasarkan master rumpun (pattern).';

    public function handle(SubjectGroupResolver $resolver): int
    {
        $query = Subject::query();

        if (! $this->option('force')) {
            $query->whereNull('subject_group_id');
        }

        $updated = 0;
        $skipped = 0;

        $query->orderBy('name')->chunkById(200, function ($subjects) use ($resolver, &$updated, &$skipped) {
            foreach ($subjects as $subject) {
                $before = $subject->subject_group_id;
                $resolver->syncSubject($subject, (bool) $this->option('force'));

                if ($subject->subject_group_id !== $before) {
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Selesai: {$updated} mapel dipetakan, {$skipped} tidak berubah.");

        return self::SUCCESS;
    }
}
