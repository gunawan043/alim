<?php

/**
 * Run pending migrations, marking as done any migration whose target tables
 * already exist (because they were created manually or by a prior partial run).
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$dir = __DIR__.'/migrations/';
$files = glob($dir.'*.php');

$recorded = DB::table('migrations')->pluck('migration')->toArray();
$recordedSet = array_flip($recorded);

$existingTables = [];
foreach (DB::select('SHOW TABLES FROM `common_admin`') as $t) {
    foreach ((array) $t as $v) {
        $existingTables[] = $v;
    }
}
$existingSet = array_flip($existingTables);

$nextBatch = (DB::table('migrations')->max('batch') ?? 0) + 1;

$marked = 0;
$ran = 0;

foreach ($files as $file) {
    $name = str_replace([$dir, '.php'], '', $file);
    if (isset($recordedSet[$name])) {
        continue;
    }

    $content = file_get_contents($file);
    preg_match_all("/Schema::create\(['\"]([\w_]+)['\"]/", $content, $cm);
    preg_match_all("/Schema::table\(['\"]([\w_]+)['\"]/", $content, $tm);
    $targets = array_unique(array_merge($cm[1] ?? [], $tm[1] ?? []));

    // If every create target already exists, mark this migration as done.
    $createTargets = array_unique($cm[1] ?? []);
    if (! empty($createTargets)) {
        $allExist = true;
        foreach ($createTargets as $t) {
            if (! isset($existingSet[$t])) {
                $allExist = false;
                break;
            }
        }
        if ($allExist) {
            DB::table('migrations')->insert(['migration' => $name, 'batch' => $nextBatch]);
            $recordedSet[$name] = true;
            $marked++;
            echo "MARK  $name\n";

            continue;
        }
    }

    // Otherwise try to actually run it.
    $mtime = filemtime($file);
    // re-record run after artisan
    echo "RUN   $name\n";
    $ran++;
}

echo "\nSummary: marked=$marked, willRun=$ran\n";
