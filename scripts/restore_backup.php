<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$backup = file_get_contents('/tmp/backup_2310.sql');

/** Ambil isi VALUES dari INSERT INTO `tabel` VALUES ... ; */
function valuesBlock(string $sql, string $table): ?string {
    $start = strpos($sql, 'INSERT INTO `'.$table.'` VALUES');
    if ($start === false) return null;
    $p = $start + strlen('INSERT INTO `'.$table.'` VALUES');
    while ($p < strlen($sql) && $sql[$p] !== '(') $p++;
    $depth = 0; $inq = false; $esc = false; $end = false;
    for ($i = $p; $i < strlen($sql); $i++) {
        $ch = $sql[$i];
        if ($inq) { if ($esc) $esc=false; elseif ($ch=='\\') $esc=true; elseif ($ch=="'") $inq=false; continue; }
        if ($ch=="'") { $inq=true; continue; }
        if ($ch=='(') { $depth++; continue; }
        if ($ch==')') {
            $depth--;
            if ($depth === 0 && $i+1 < strlen($sql) && $sql[$i+1] === ';') { $end = true; break; }
        }
    }
    if (! $end) return null;
    return substr($sql, $p, $i-$p+1);
}

/** Parse isi VALUES (mulai dari '(' ... ')') menjadi daftar baris tuple. Setiap tuple = array nilai mentah (string|null). */
function parseValues(string $block): array {
    // pisahkan tuple: scan karakter
    $tuples = [];
    $depth = 0; $inq = false; $esc = false; $cur = '';
    $n = strlen($block);
    for ($i = 0; $i < $n; $i++) {
        $ch = $block[$i];
        if ($inq) {
            $cur .= $ch;
            if ($esc) { $esc=false; }
            elseif ($ch === '\\') { $esc=true; }
            elseif ($ch === "'") { $inq=false; }
            continue;
        }
        if ($ch === "'") { $inq=true; $cur .= $ch; continue; }
        if ($ch === '(') { if ($depth === 0) { $cur = ''; } $depth++; $cur .= $ch; continue; }
        if ($ch === ')') { $depth--; $cur .= $ch; if ($depth === 0) { $tuples[] = $cur; } continue; }
        if ($depth === 0) continue; // di luar tuple (spasi/komsa antar tuple)
        $cur .= $ch;
    }
    $out = [];
    foreach ($tuples as $t) {
        $out[] = parseTuple(substr($t, 1, -1));
    }
    return $out;
}

function parseTuple(string $inner): array {
    $vals = []; $cur = ''; $depth = 0; $inq = false; $esc = false;
    $n = strlen($inner);
    for ($i = 0; $i < $n; $i++) {
        $ch = $inner[$i];
        if ($inq) {
            $cur .= $ch;
            if ($esc) $esc=false;
            elseif ($ch === '\\') $esc=true;
            elseif ($ch === "'") $inq=false;
            continue;
        }
        if ($ch === "'") { $inq = true; $cur .= $ch; continue; }
        if ($ch === '(') { $depth++; $cur .= $ch; continue; }
        if ($ch === ')') { $depth--; $cur .= $ch; continue; }
        if ($ch === ',' && $depth === 0) {
            $vals[] = trim($cur); $cur = ''; continue;
        }
        $cur .= $ch;
    }
    if (trim($cur) !== '') $vals[] = trim($cur);
    // ubah representasi mentah ke nilai PHP
    return array_map('unpackValue', $vals);
}

function unpackValue(string $v) {
    $v = trim($v);
    if ($v === 'NULL' || $v === '') return null;
    if ($v[0] === "'") {
        // string literal: hilangkan quote luar, unescape \' dan \\
        $s = substr($v, 1, -1);
        $s = str_replace("\\'", "'", $s);
        $s = str_replace('\\\\', '\\', $s);
        return $s;
    }
    if (strtolower($v) === 'true' || $v === '1') return true;
    if (strtolower($v) === 'false' || $v === '0') return false;
    if (is_numeric($v)) return $v;
    return $v;
}

function columnsNow(string $table): array {
    $rows = DB::select("desc `$table`");
    return array_map(fn($r)=>$r->Field, $rows);
}

function columnsBackup(string $sql, string $table): array {
    $m = [];
    $start = strpos($sql, 'CREATE TABLE `'.$table.'`');
    if ($start === false) return $m;
    $seg = substr($sql, $start);
    // ambil antara '(' pembuka definisi kolam sampai ') ENGINE' (atau ')' depth-0 pertama setelah kolam)
    $p = strpos($seg, '(');
    if ($p === false) return $m;
    $depth = 0; $end = false;
    for ($i = $p; $i < strlen($seg); $i++) {
        if ($seg[$i] === '(') { $depth++; continue; }
        if ($seg[$i] === ')') { $depth--; if ($depth === 0) { $end = true; break; } }
    }
    if (! $end) return $m;
    $body = substr($seg, $p+1, $i-$p-1);
    // Ambil hanya definisi kolam: backtick-nama yang diikuti tipe data (hindari nama tabel dalam FOREIGN KEY references)
    preg_match_all('/`([a-z0-9_]+)`\s+(?:char|varchar|text|longtext|tinyint|longtext|enum|datetime|timestamp|date|decimal|year|bigint|int|smallint|float|double|binary|blob|json|mediumtext|set|bit|varbinary|set)\b/i', $body, $mm, PREG_SET_ORDER);
    foreach ($mm as $x) $m[] = $x[1];
    return $m;
}

$tables = [
    'roles',
    'indonesia_provinces','indonesia_cities','indonesia_districts','indonesia_villages',
    'work_units','jenis_gtk','structural_positions','schools',
    'users','model_has_roles','gtk_profiles','gtk_employments','gtk_addresses','gtk_contacts','gtk_educations',
];

$total = 0;
foreach ($tables as $table) {
    $block = valuesBlock($backup, $table);
    if ($block === null) { echo "$table: tidak ditemukan di backup\n"; continue; }
    $tuples = parseValues($block);
    if (empty($tuples)) { echo "$table: 0 rows\n"; continue; }

    $colsNow = columnsNow($table);
    $colsNowSet = array_flip($colsNow);
    $colsBak = columnsBackup($backup, $table);

    $idIdx = array_search('id', $colsNow);
    $existingIds = [];
    if ($idIdx !== false) {
        $existingIds = array_flip(DB::table($table)->whereNotNull('id')->pluck('id')->toArray());
    }
    $skipped = 0;
    // mapping: indeks kolam backup -> nama kolam sekarang (urutan kolam backup dianggap sama sekarang kecuali tambahan baru)
    $map = []; // sekarang index -> backup index
    foreach ($colsBak as $i => $name) {
        if (in_array($name, $colsNow)) $map[array_search($name, $colsNow)] = $i;
    }
    $newCols = array_diff($colsNow, $colsBak);

    // Auto NULL-kan FK ke tabel tujuan yang masih kosong di DB sekarang (mis. academic_years, study_groups)
    $fkTargets = [
        'indonesia_cities' => ['province_code'=>'indonesia_provinces'],
        'indonesia_districts' => ['city_code'=>'indonesia_cities'],
        'indonesia_villages' => ['district_code'=>'indonesia_districts'],
        'work_units' => ['domain_id'=>'domains','divisi_id'=>'divisis'],
        'schools' => ['work_unit_id'=>'work_units','province_code'=>'indonesia_provinces','city_code'=>'indonesia_cities','district_code'=>'indonesia_districts','village_code'=>'indonesia_villages'],
        'structural_positions' => ['domain_id'=>'domains','jenis_gtk_id'=>'jenis_gtk','role_id'=>'roles'],
        'gtk_profiles' => ['work_unit_id'=>'work_units','household_id'=>'gtk_households','spouse_gtk_profile_id'=>'gtk_profiles'],
        'gtk_employments' => ['user_id'=>'users','school_id'=>'schools','jenis_gtk_id'=>'jenis_gtk','jabatan_id'=>'structural_positions','academic_year_id'=>'academic_years','study_group_id'=>'study_groups'],
        'gtk_addresses' => ['gtk_profile_id'=>'gtk_profiles','province_code'=>'indonesia_provinces','city_code'=>'indonesia_cities','district_code'=>'indonesia_districts','village_code'=>'indonesia_villages'],
        'gtk_contacts' => ['user_id'=>'users'],
        'gtk_educations' => ['user_id'=>'users','verified_by'=>'users'],
        'model_has_roles' => ['role_id'=>'roles','model_id'=>'users'],
    ];
    $nullCols = [];
    foreach ($colsNow as $ci => $name) {
        if (!preg_match('/^(.+)_(id|code)$/', $name, $m)) continue;
        $target = $fkTargets[$table][$name] ?? ($m[1].'s');
        if ($target === $table) continue;
        if (! Schema::hasTable($target)) { $nullCols[] = $ci; echo "  ($table) kolam $name NULL-kan (tabel $target tidak ada)\n"; continue; }
        if (DB::table($target)->count() > 0) continue;
        $nullCols[] = $ci;
        echo "  ($table) kolam $name NULL-kan (tabel $target kosong)\n";
    }

    $insertRows = [];
    foreach ($tuples as $t) {
        $row = array_combine($colsNow, array_fill(0, count($colsNow), null));
        foreach ($t as $i => $val) {
            if (!isset($colsBak[$i])) continue;
            $name = $colsBak[$i];
            if (isset($colsNowSet[$name])) $row[$name] = $val;
        }
        foreach ($nullCols as $ci) $row[$colsNow[$ci]] = null;
        $idVal = $idIdx !== false ? ($row[$colsNow[$idIdx]] ?? null) : null;
        if ($idVal !== null && isset($existingIds[$idVal])) { $skipped++; continue; }
        $insertRows[] = $row;
        if (count($insertRows) >= 500) {
            try { DB::table($table)->insert($insertRows); $total += count($insertRows); } catch (\Exception $e) { echo "  batch insert error: ".$e->getMessage()."\n"; }
            $insertRows = [];
        }
    }
    if (! empty($insertRows)) {
        try { DB::table($table)->insert($insertRows); $total += count($insertRows); } catch (\Exception $e) {
            // coba insert per baris agar baris valid tetap masuk
            foreach ($insertRows as $r) {
                try { DB::table($table)->insert($r); $total++; } catch (\Exception $e2) { echo "  row skip: ".$e2->getMessage()."\n"; }
            }
        }
    }
    echo "$table: ".count($tuples)." rows in backup, ".($skipped ?? 0)." skipped (already exist)\n";
}
echo "TOTAL: $total\n";
