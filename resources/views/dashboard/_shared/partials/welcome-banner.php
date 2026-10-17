<?php

use Illuminate\Support\Facades\DB;

$jabatan = $this->activeJabatanLabel ?? '';

$schoolId = $this->getUserSchoolId($user);
$schoolName = $schoolId
    ? DB::table('schools')->where('id', $schoolId)->value('name')
    : null;

$hour = (int) now()->format('H');
$greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));

return [
    '_col'     => 'col-12',
    'greeting' => $greeting,
    'name'     => $user->name ?? 'Pengguna',
    'jabatan'  => $jabatan ?: 'Dashboard',
    'school'   => $schoolName,
    'date'     => now()->translatedFormat('l, d F Y'),
    'tasks'    => method_exists($this, 'detectTugasTambahan')
        ? $this->detectTugasTambahan($user)
        : [],
];
