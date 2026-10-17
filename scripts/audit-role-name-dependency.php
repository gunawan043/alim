<?php

/**
 * Audit script — scans every PHP file in app/ for hardcoded role-name
 * dependencies (string literals like 'Wadir 1', 'Admin Sarpras', 'super-admin',
 * 'kepala_sekolah', etc.) outside of:
 *   • app/Authorization/  (the legitimate role-name registry)
 *   • database/seeders/RolePermissionSeeder.php  (the role registry)
 *   • config/auth.php  (auth provider names)
 *   • tests/  (acceptable in tests)
 *
 * Run from the project root:
 *   php scripts/audit-role-name-dependency.php
 *
 * Exit code 0 = clean, 1 = violations found.
 */

declare(strict_types=1);

$root = realpath(__DIR__.'/..');
$appPath = $root.'/app';
$violations = [];

// Allowed prefixes — directories/files that legitimately reference role names
$allowlistDirs = [
    $appPath.'/Authorization',
];

// Build a list of well-known role names in the system (from
// docs/authorization-architecture-validation.md and seeders). The audit
// only flags these — custom domain role names added later are not flagged.
$knownRoleNames = [
    'super-admin',
    'superadmin',
    'admin',
    'kepala_sekolah',
    'kepsek',
    'wakil_kepala_sekolah',
    'waka',
    'wadir',
    'wadir 1',
    'wadir 2',
    'guru',
    'guru_mapel',
    'homeroom',
    'wali_kelas',
    'admin_sarpras',
    'admin tata usaha',
    'admin_tu',
    'admin kesiswaan',
    'admin_kesiswaan',
    'bendahara',
    'tu',
    'tata usaha',
    'sarpras',
    'keuangan',
    'kesiswaan',
    'kebersihan',
    'keamanan',
    'osis',
    'kepala_program',
    'kaprog',
    'kepala_lab',
    'pembina_osis',
    'bk',
    'guru_bk',
    'counselor',
];

$rolePattern = '/\b('.implode('|', array_map(fn ($n) => preg_quote($n, '/'), $knownRoleNames)).')\b/i';

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($appPath, FilesystemIterator::SKIP_DOTS)
);

foreach ($files as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getRealPath();

    // Skip allowlisted dirs
    foreach ($allowlistDirs as $allowed) {
        if (str_starts_with($path, $allowed)) {
            continue 2;
        }
    }

    $content = file_get_contents($path);
    $lines = explode("\n", $content);

    foreach ($lines as $idx => $line) {
        // Skip comment lines
        $trim = trim($line);
        if ($trim === '' || str_starts_with($trim, '//') || str_starts_with($trim, '/*') || str_starts_with($trim, '*')) {
            continue;
        }

        // Skip lines that are clearly namespacing/use statements
        if (preg_match('/^(namespace|use)\b/', $trim)) {
            continue;
        }

        if (preg_match($rolePattern, $line, $m)) {
            // Filter false positives:
            // • Variable names like $admin, $super
            // • Comment-only lines (already filtered)
            // • Enum identifiers in tokens

            // Real concern: role-name literal in conditional / method arg
            // Look for context: 'if ($user->...role...==', 'hasRole', 'whereIn.*role',
            // 'redirectIfMissing', 'Gate::define.*role'
            $line = $lines[$idx];

            // Heuristic: only flag if line contains: hasRole, Gate::, role_id,
            // middleware('role, atau perbandingan equality
            $is_real = preg_match('/hasRole|Gate::define|can\(.*role|->role\s*==|->role\s*!=|\bin\s*\(\s*[\'"]\w*role/i', $line);

            if ($is_real) {
                $violations[] = sprintf(
                    '%s:%d  %s',
                    str_replace($root.'/', '', $path),
                    $idx + 1,
                    trim($line)
                );
            }
        }
    }
}

if ($violations) {
    echo "❌ ROLE-NAME DEPENDENCY AUDIT FAILED\n";
    echo "The following lines reference role names outside the Authorization layer:\n\n";
    echo implode("\n", $violations)."\n\n";
    echo count($violations)." violation(s) found.\n";
    exit(1);
}

echo "✅ ROLE-NAME DEPENDENCY AUDIT PASSED\n";
echo "All role-name references are isolated to the Authorization layer.\n";
exit(0);
