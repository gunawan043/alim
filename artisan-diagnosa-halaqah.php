<?php

/**
 * Diagnosa sementara: cek kenapa tombol "Tambah Halaqah" tidak muncul
 * untuk akun koordinator tahfidz.
 *
 * Jalankan di server produksi, lalu HAPUS file ini setelah selesai:
 *   php artisan tinker --execute="require base_path('artisan-diagnosa-halaqah.php');"
 *
 * Ubah $cariEmail di bawah menjadi email akun koordinator yang bermasalah.
 */

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\SchoolGroupService;
use App\Services\TahfidzAccess;
use Illuminate\Support\Facades\DB;

$cariEmail = $cariEmail ?? 'ganti-dengan-email-koordinator';

$user = User::where('email', $cariEmail)->first();

if (! $user && isset($argv[1])) {
    $user = User::where('email', $argv[1])->first();
}

if (! $user) {
    echo "Akun dengan email {$cariEmail} tidak ditemukan.".PHP_EOL;

    return;
}

$access = app(TahfidzAccess::class);
$activeYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::orderByDesc('start_date')->first();
$employment = $user->employment;
$schoolId = $access->schoolIdFor($user);
$school = $schoolId ? \App\Models\School::find($schoolId) : null;

// Koordinator bisa tercatat di SK kepegawaian ATAU di halaman
// Other Teacher Tasks — keduanya diperiksa di sini.
$otherTasks = DB::table('other_teacher_tasks')->where('teacher_id', $user->id)->get();

$baris = function (string $label, $nilai) {
    echo '  '.str_pad($label, 44).': '.(is_bool($nilai) ? ($nilai ? 'YA' : 'TIDAK') : (is_null($nilai) || $nilai === '' ? '(kosong)' : $nilai)).PHP_EOL;
};

echo '== AKUN =='.PHP_EOL;
$baris('Nama', $user->name);
$baris('Aktif', (bool) $user->is_active);
$baris('Role Spatie', $user->getRoleNames()->implode(', ') ?: '(tidak ada)');
$baris('Super admin', $access->isSuperAdmin($user));
$baris('Departemen Tahfidz', $access->isDepartment($user));

echo PHP_EOL.'== KEPEGAWAIAN =='.PHP_EOL;
$baris('Satuan pendidikan (resolusi)', $school?->name);
$baris('school_id kepegawaian terisi', (bool) $employment?->school_id);
$baris('Gender sekolah', $school?->school_gender);
$baris('Jabatan', $employment?->jabatan);
$baris('SchoolGroupService::getUserSchool', SchoolGroupService::getUserSchool($user)?->name);

echo PHP_EOL.'== TUGAS TAMBAHAN (gtk_additional_tasks) =='.PHP_EOL;
$tugas = DB::table('gtk_additional_tasks')->where('user_id', $user->id)->get(['nama_tugas', 'tmt', 'tst']);
if ($tugas->isEmpty()) {
    echo '  (tidak ada baris untuk akun ini)'.PHP_EOL;
}
foreach ($tugas as $t) {
    $cocok = str_contains(strtolower((string) $t->nama_tugas), 'koordinator')
        && (bool) preg_match('/tahfi[dz]/i', (string) $t->nama_tugas);
    echo '  - '.$t->nama_tugas
        .' | tmt '.($t->tmt ?: '-')
        .' | tst '.($t->tst ?: 'tanpa batas')
        .' | koordinator+tahfidz: '.($cocok ? 'cocok' : 'tidak cocok').PHP_EOL;
}

echo PHP_EOL.'== TUGAS TAMBAHAN (other_teacher_tasks) =='.PHP_EOL;
if ($otherTasks->isEmpty()) {
    echo '  (tidak ada baris untuk akun ini)'.PHP_EOL;
}
foreach ($otherTasks as $t) {
    echo '  - '.$t->task_name
        .' | sekolah '.($t->school_id ?: '-')
        .' | aktif '.($t->is_active ? 'ya' : 'tidak').PHP_EOL;
}

echo PHP_EOL.'== HASIL PENGECEKAN =='.PHP_EOL;
$baris('hasCoordinatorTask()', $access->hasCoordinatorTask($user, $schoolId));
$baris('isSchoolCoordinator(user, sekolah)', $access->isSchoolCoordinator($user, $schoolId));
$baris('isSchoolCoordinator(+ tahun ajaran)', $access->isSchoolCoordinator($user, $schoolId, $activeYear?->id));
$baris('canViewAllSchools()', $access->canViewAllSchools($user));

$canManage = $access->isSuperAdmin($user)
    || $access->isSchoolCoordinator($user, $schoolId, $activeYear?->id);

echo PHP_EOL.'== KESIMPULAN =='.PHP_EOL;
$baris('canManage (berhak tombol)', $canManage);

if (! $canManage && $access->hasCoordinatorTask($user, $schoolId)) {
    echo PHP_EOL.'  Sebab: tugas tambahan dikenali, tetapi akun tidak punya baris'.PHP_EOL;
    echo '  kepegawaian di satuan pendidikan tersebut. Tautkan GTK ke sekolah'.PHP_EOL;
    echo '  itu pada halaman data kepegawaian.'.PHP_EOL;
}

if (! $canManage && ! $access->hasCoordinatorTask($user, $schoolId)) {
    echo PHP_EOL.'  Sebab: nama tugas tambahan tidak memuat "Koordinator" DAN'.PHP_EOL;
    echo '  "Tahfidz/Tahfiz", atau tanggal berakhirnya (tst) sudah lewat.'.PHP_EOL;
}
