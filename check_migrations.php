<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Cek isi tabel migrations
echo "=== Isi tabel migrations (terakhir 10) ===\n";
$rows = DB::table('migrations')->orderBy('id', 'desc')->limit(10)->get();
foreach ($rows as $r) {
    echo "  - [{$r->batch}] {$r->migration}\n";
}

// 2. Cek apakah ada asset_id di tabel dormitory_inventories
echo "\n=== Kolom asset_id dan category_id ===\n";
$db = DB::connection()->getConfig('database');
$cols = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'dormitory_inventories' AND COLUMN_NAME IN ('asset_id', 'category_id')", [$db]);
foreach ($cols as $c) {
    echo "  ✓ {$c->COLUMN_NAME}\n";
}
