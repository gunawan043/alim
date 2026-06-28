<?php

// Smoke test for GTK workload analysis pipeline
// Tests: 1 event → 1 run; 2 burst events → 1 run (dedup via unique job)

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== GTK Workload Smoke Test ===\n";

// Clear test data (FK-aware order)
DB::table('gtk_gap_summaries')->delete();
DB::table('gtk_analysis_runs')->delete();

// Test 1: Dispatch single GtkProfileUpdated event
echo "\nTest 1: Single GtkProfileUpdated event\n";
\App\Events\GtkProfileUpdated::dispatch(
    (string) \Illuminate\Support\Str::uuid(),
    'test_change',
    'manual_trigger',
    'test_ref_1'
);

echo "Waiting 4s for processing...\n";
sleep(4);
$count1 = DB::table('gtk_analysis_runs')->where('trigger_source', 'GtkProfileUpdated')->count();
echo "Runs after 1 event: {$count1}\n";
echo 'Result: '.($count1 === 1 ? 'PASS' : 'FAIL')."\n";

// Test 2: Dispatch 2 simultaneous events (should dedup)
echo "\nTest 2: Two burst GtkProfileUpdated events (dedup)\n";
\App\Events\GtkProfileUpdated::dispatch(
    (string) \Illuminate\Support\Str::uuid(),
    'test_change',
    'manual_trigger',
    'test_ref_2a'
);
\App\Events\GtkProfileUpdated::dispatch(
    (string) \Illuminate\Support\Str::uuid(),
    'test_change',
    'manual_trigger',
    'test_ref_2b'
);

echo "Waiting 4s for processing...\n";
sleep(4);
$count2 = DB::table('gtk_analysis_runs')->where('trigger_source', 'GtkProfileUpdated')->count();
echo "Runs after burst: {$count2} (should be 2 — one from test 1, one deduped)\n";
echo 'Dedup Result: '.($count2 === 2 ? 'PASS' : 'FAIL')."\n";

// Test 3: Check for failed runs
echo "\nTest 3: Check for failed runs\n";
$failed = DB::table('gtk_analysis_runs')->where('status', 3)->count();
$completed = DB::table('gtk_analysis_runs')->where('status', 2)->count();
echo "Failed runs: {$failed}\n";
echo "Completed runs: {$completed}\n";
echo 'Total runs: '.DB::table('gtk_analysis_runs')->count()."\n";

// Inspect summary structure
echo "\nTest 4: Inspect completed run structure\n";
$run = DB::table('gtk_analysis_runs')->where('status', 2)->first();
if ($run) {
    echo "Run ID: {$run->id}\n";
    echo "Summary JSON (first 300 chars):\n";
    echo substr($run->summary ?? 'NULL', 0, 300)."\n";
}

echo "\n=== Done ===\n";
