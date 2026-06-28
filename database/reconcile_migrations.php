<?php

/**
 * Reconcile migrations:
 *  - Mark migration done if all tables referenced (create OR alter) already exist
 *  - Or if every CREATE target already exists OR there are no CREATE targets
 *    (meaning the migration is alters only and the table must already exist)
 *
 * Designed to be re-run after each partial `php artisan migrate` so we
 * progressively skip migrations whose objects already exist in the DB.
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

foreach ($files as $file) {
    $name = str_replace([$dir, '.php'], '', $file);
    if (isset($recordedSet[$name])) {
        continue;
    }

    $content = file_get_contents($file);

    // Collect all tables the migration touches.
    preg_match_all("/Schema::create\(['\"]([\w_]+)['\"]/", $content, $cm);
    preg_match_all("/Schema::table\(['\"]([\w_]+)['\"]/", $content, $tm);

    $createTargets = array_unique($cm[1] ?? []);
    $alterTargets = array_unique($tm[1] ?? []);

    // For migrations that only CREATE: every create target must already exist.
    if (! empty($createTargets) && empty($alterTargets)) {
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

    // For migrations with ALTERs (no CREATEs): every alter target must exist.
    if (! empty($alterTargets) && empty($createTargets)) {
        $allExist = true;
        foreach ($alterTargets as $t) {
            if (! isset($existingSet[$t])) {
                $allExist = false;
                break;
            }
        }
        if ($allExist) {
            DB::table('migrations')->insert(['migration' => $name, 'batch' => $nextBatch]);
            $recordedSet[$name] = true;
            $marked++;
            echo "MARK  $name (alters)\n";

            continue;
        }
    }
}

echo "\nMarked: $marked\n";
