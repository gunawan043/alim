<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Asrama: pengingat keterlambatan kembali ─────────────────────
// Dijalankan tiap menit; pengingat mulai berjalan saat batas waktu
// kepulangan terlewati, lalu diulang sesuai interval per asrama
// (Pengaturan → Notifikasi Kembali Asrama).
Schedule::command('dormitory:process-overdue')->everyMinute()->withoutOverlapping();

// Pengingat operasional Laboratorium IPA.
Schedule::command('lab:send-reminders')->dailyAt('07:20')->withoutOverlapping();
