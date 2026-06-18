<?php

namespace App\Console\Commands;

use App\Models\GtkAnalysisRun;
use App\Models\School;
use App\Services\GtkAnalysisEngine;
use Illuminate\Console\Command;

class RecalculateGtkWorkload extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gtk:analyze
                            {--school= : Specific school_id to analyze}
                            {--academic-year= : Specific academic_year_id to scope to}
                            {--scope=school : scope of analysis (school|global)}
                            {--trigger=cli : trigger source label}';

    /**
     * The console command description.
     */
    protected $description = 'Trigger GTK workload & gap analysis. Can be run per-school, globally, or scoped to an academic year.';

    /**
     * Execute the console command.
     */
    public function handle(GtkAnalysisEngine $engine): int
    {
        $schoolOption = $this->option('school');
        $scope = $this->option('scope');
        $trigger = $this->option('trigger');
        $ayId = $this->option('academic-year');

        if ($scope === 'global') {
            $this->info('Running global GTK analysis...');
            $run = $engine->run([
                'academic_year_id' => $ayId,
                'scope' => GtkAnalysisRun::SCOPE_GLOBAL,
                'trigger_source' => $trigger,
            ]);

            $this->line('  Run ID:  '.$run->id);
            $this->line('  Status:  '.$run->status_label);
            $this->line('  Started: '.$run->started_at);
            $this->line('  Finished:'.$run->finished_at);
            $this->line('  Summary:'.json_encode($run->summary, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($schoolOption) {
            $this->info("Running analysis for school: {$schoolOption}");
            $run = $engine->run([
                'school_id' => $schoolOption,
                'academic_year_id' => $ayId,
                'scope' => GtkAnalysisRun::SCOPE_SCHOOL,
                'trigger_source' => $trigger,
            ]);

            $this->line('  Run ID:  '.$run->id);
            $this->line('  Status:  '.$run->status_label);
            $this->line('  Summary:'.json_encode($run->summary, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $schools = School::orderBy('name')->get();
        $this->info("Running analysis for {$schools->count()} schools...");

        $bar = $this->output->createProgressBar($schools->count());
        $bar->start();

        $totalRows = 0;
        foreach ($schools as $school) {
            try {
                $run = $engine->run([
                    'school_id' => $school->id,
                    'academic_year_id' => $ayId,
                    'scope' => GtkAnalysisRun::SCOPE_SCHOOL,
                    'trigger_source' => $trigger,
                ]);
                $totalRows += $run->summary['subject_rows'] ?? 0;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("  Failed: {$school->name} ({$e->getMessage()})");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Total subject rows across all schools: {$totalRows}");

        return self::SUCCESS;
    }
}
