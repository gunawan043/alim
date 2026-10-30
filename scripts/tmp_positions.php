<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$out = 'db='.DB::connection()->getDatabaseName()."\n";
$out .= 'default='.config('database.default')."\n";
$out .= 'tables='.count(DB::select('show tables'))."\n";
foreach (DB::select('show tables') as $r) {
    $a = (array) $r;
    $n = array_values($a)[0];
    if (str_contains($n, 'structural')) {
        $out .= $n.' => '.DB::table($n)->count()."\n";
    }
}
file_put_contents('/tmp/pos_out.txt', $out);
