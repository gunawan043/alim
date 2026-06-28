<?php

// Run via: php -r "$(cat f9_verify.php)"
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$all = DB::select('SHOW TABLES');
$f9 = [];
foreach ($all as $row) {
    $keys = array_keys((array) $row);
    $tbl = $row->{$keys[0]};
    if (preg_match('/^(evaluation_tp|bank_soal|soal|paket_soal|kisi_kisi|exam_|item_analysis|soal_option|admin_nilai_sumatif)/', $tbl)) {
        $f9[] = $tbl;
    }
}
sort($f9);
echo 'F9 tables found: '.count($f9)."\n";
foreach ($f9 as $t) {
    $count = DB::table($t)->count();
    echo sprintf("  %-50s rows=%d\n", $t, $count);
}
