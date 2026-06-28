<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestGtkAnalysisEngine extends Command
{
    protected $signature = 'test:gtk-analysis';

    protected $description = 'Test the GTK Analysis Engine end-to-end';

    public function handle()
    {
        $this->info('Checking required tables...');

        $tables = ['gtk_analysis_runs', 'gtk_gap_summaries'];
        foreach ($tables as $t) {
            if (! Schema::hasTable($t)) {
                $this->error("Table {$t} does not exist. Run migrations first.");

                return 1;
            }
            $this->info("  ✓ {$t}");
        }

        // Clean up any existing test data from previous runs
        $this->info('Cleaning up previous test data...');
        DB::table('gtk_gap_summaries')->where('description', 'like', '%TEST ANALYSIS%')->delete();
        DB::table('gtk_analysis_runs')->where('description', 'like', '%TEST ANALYSIS%')->delete();
        DB::table('teaching_assignments')->where('id', 'like', 'ta-%')->delete();
        $this->info('  ✓ Cleaned');

        $schoolId = DB::table('schools')->where('name', 'Sekolah Test')->value('id');
        $ayId = DB::table('academic_years')->where('name', 'TA TEST 2025/2026')->value('id');
        $sgId = DB::table('study_groups')->where('code', 'SG-TEST-001')->value('id');
        $decId = DB::table('institution_decrees')->where('number', '001/TEST')->value('id');

        if (! $schoolId || ! $ayId || ! $sgId || ! $decId) {
            $this->warn('Test data not seeded yet. Running seeder...');
            $seederPath = dirname(__FILE__).'/../seed-gtk-analysis-test.php';
            if (file_exists($seederPath)) {
                include $seederPath;
            }
            $schoolId = DB::table('schools')->where('name', 'Sekolah Test')->value('id');
            $ayId = DB::table('academic_years')->where('name', 'TA TEST 2025/2026')->value('id');
            $sgId = DB::table('study_groups')->where('code', 'SG-TEST-001')->value('id');
            $decId = DB::table('institution_decrees')->where('number', '001/TEST')->value('id');
        }

        $this->info("School: $schoolId | Academic Year: $ayId | Study Group: $sgId");

        // Check teaching assignments
        $taCount = DB::table('teaching_assignments')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $ayId)
            ->count();
        $this->info("Teaching assignments: $taCount");

        // Now run the analysis engine
        $this->newLine();
        $this->info('========================================');
        $this->info('  RUNNING GTK ANALYSIS ENGINE');
        $this->info('========================================');
        $this->newLine();

        $analysis = new \App\Services\GtkAnalysisEngine;
        $result = $analysis->analyze($schoolId, $ayId);

        $this->info('');
        $this->info('── SUMMARY ──────────────────────────────');
        $this->line('  Run ID: '.$result['run_id']);
        $this->line('  Total GTK Active: '.$result['stats']['total_gtk_active']);
        $this->line('  Total Teaching Hours (ideal): '.$result['stats']['total_ideal_weekly_hours']);
        $this->line('  Total Hours Assigned: '.$result['stats']['total_assigned_hours']);
        $this->line('  Subjects Requiring Coverage: '.$result['stats']['subjects_needing_coverage']);
        $this->line('  Teacher-Gaps Found: '.$result['stats']['teacher_gaps_count']);
        $this->line('  Surplus GTK Found: '.$result['stats']['surplus_gtk_count']);

        if (! empty($result['gaps'])) {
            $this->newLine();
            $this->info('── GAPS DETECTED ─────────────────────────');
            foreach ($result['gaps'] as $gap) {
                if ($gap['gap_type'] === 'no_teaching_assignment') {
                    $this->line('');
                    $this->warn('  ⚠  '.$gap['subject_name']);
                    $this->line('     Type: BELUM ADA TEACHING ASSIGNMENT');
                    $this->line("     Ideal Hours: {$gap['ideal_hours']} jam/mgg");
                    $this->line("     Assignment Hours: {$gap['assigned_hours']} jam/mgg");
                } elseif ($gap['gap_type'] === 'underloaded') {
                    $this->line('');
                    $this->warn('  ⚠  '.$gap['teacher_name']);
                    $this->line('     Status: UNDERLOADED');
                    $this->line("     Assigned: {$gap['current_hours']} jam/mgg");
                    $this->line("     Minimum: {$gap['minimum_hours']} jam/mgg");
                    $this->line("     Deficit: {$gap['gap_amount']} jam/mgg");
                } elseif ($gap['gap_type'] === 'overloaded') {
                    $this->line('');
                    $this->warn('  ⚠  '.$gap['teacher_name']);
                    $this->line('     Status: OVERLOADED');
                    $this->line("     Assigned: {$gap['current_hours']} jam/mgg");
                    $this->line("     Maximum: {$gap['maximum_hours']} jam/mgg");
                    $this->line("     Excess: {$gap['gap_amount']} jam/mgg");
                }
            }
        }

        if (! empty($result['summary_rows'])) {
            $this->newLine();
            $this->info('── GAP SUMMARIES (per subject) ────────────');
            foreach ($result['summary_rows'] as $row) {
                $this->line('');
                $statusIcon = match ($row['coverage_status']) {
                    'adequate' => '✓',
                    'partial' => '⚠',
                    'none' => '✗',
                    default => '?',
                };
                $this->line("  {$statusIcon} {$row['subject_name']}");
                $this->line("    Ideal: {$row['ideal_weekly_hours']} jam | Assigned: {$row['total_assigned_hours']} jam | Coverage: {$row['coverage_percentage']}%");
                $this->line("    Teachers: {$row['teacher_count']} | Role: {$row['expected_role']} | Required: {$row['expected_count']}");
            }
        }

        $this->newLine();

        // Verify the saved records
        $runs = DB::table('gtk_analysis_runs')->where('description', 'like', '%TEST ANALYSIS%')->get();
        $summaries = DB::table('gtk_gap_summaries')->where('description', 'like', '%TEST ANALYSIS%')->get();
        $this->info('  Saved analysis runs: '.$runs->count());
        $this->info('  Saved gap summaries: '.$summaries->count());

        foreach ($summaries as $s) {
            $this->line("    [{$s->gap_type}] {$s->subject_name} - {$s->coverage_percentage}% coverage");
        }

        $this->newLine();
        $this->info('========================================');
        if ($result['stats']['teacher_gaps_count'] > 0) {
            $this->warn('  ANALYSIS COMPLETE — GAPS DETECTED!');
        } else {
            $this->success('  ANALYSIS COMPLETE — NO GAPS!');
        }
        $this->info('========================================');

        return 0;
    }
}
