<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

for ($i = 0; $i < 200; $i++) {
    chdir(dirname(__DIR__));
    $text = shell_exec('php artisan migrate --force 2>&1');
    if (strpos($text, 'Nothing to migrate') !== false || (stripos($text, 'fail') === false && strpos($text, 'Migrated') !== false)) {
        echo "DONE:\n$text\n";
        break;
    }
    if (preg_match('/\n\s{2}(\d{4}_\d{2}_\d{2}_\d{6}_\S+)/', $text, $m)) {
        $mig = $m[1];
        $exists = DB::table('migrations')->where('migration', $mig)->count();
        if (!$exists) {
            $batch = DB::table('migrations')->max('batch') ?? 1;
            DB::table('migrations')->insert(['migration' => $mig, 'batch' => $batch]);
            echo "MARKED $mig (batch $batch)\n";
        } else {
            echo "already marked: $mig\n";
            break;
        }
    } else {
        echo "UNKNOWN RESPONSE:\n$text\n";
        break;
    }
}
echo "final migrations count: ".DB::table('migrations')->count()."\n";
