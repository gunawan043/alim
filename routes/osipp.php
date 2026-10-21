<?php

use App\Http\Controllers\Osipp\OsippActivityController;
use App\Http\Controllers\Osipp\OsippCoachingController;
use App\Http\Controllers\Osipp\OsippDashboardController;
use App\Http\Controllers\Osipp\OsippDocumentController;
use App\Http\Controllers\Osipp\OsippEvaluationController;
use App\Http\Controllers\Osipp\OsippMemberController;
use App\Http\Controllers\Osipp\OsippPeriodController;
use App\Http\Controllers\Osipp\OsippProgramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| OSIPP — RUANG KERJA GURU KOORDINATOR
|--------------------------------------------------------------------------
| Modul ini tidak mengelola keuangan. Fokusnya pembinaan, administrasi,
| program kerja, verifikasi kegiatan, dan evaluasi kepengurusan.
*/
Route::middleware(['auth', 'employee.access', 'school.context', 'organization.context', 'osipp.access'])
    ->prefix('{userId}/osipp')
    ->name('osipp.')
    ->group(function () {
        Route::get('/', [OsippDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('periode')->name('periode.')->group(function () {
            Route::get('/', [OsippPeriodController::class, 'index'])->name('index');
            Route::post('/', [OsippPeriodController::class, 'store'])->name('store');
            Route::put('/{period}', [OsippPeriodController::class, 'update'])->name('update');
            Route::delete('/{period}', [OsippPeriodController::class, 'destroy'])->name('destroy');
            Route::post('/{period}/aktifkan', [OsippPeriodController::class, 'activate'])->name('activate');
        });

        Route::get('/siswa/cari', [OsippMemberController::class, 'searchStudents'])->name('siswa.search');

        Route::prefix('anggota')->name('anggota.')->group(function () {
            Route::get('/', [OsippMemberController::class, 'index'])->name('index');
            Route::post('/', [OsippMemberController::class, 'store'])->name('store');
            Route::put('/{member}', [OsippMemberController::class, 'update'])->name('update');
            Route::delete('/{member}', [OsippMemberController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('program')->name('program.')->group(function () {
            Route::get('/', [OsippProgramController::class, 'index'])->name('index');
            Route::post('/', [OsippProgramController::class, 'store'])->name('store');
            Route::put('/{program}', [OsippProgramController::class, 'update'])->name('update');
            Route::delete('/{program}', [OsippProgramController::class, 'destroy'])->name('destroy');
            Route::post('/{program}/progress', [OsippProgramController::class, 'updateProgress'])->name('progress');
        });

        Route::prefix('kegiatan')->name('kegiatan.')->group(function () {
            Route::get('/', [OsippActivityController::class, 'index'])->name('index');
            Route::post('/', [OsippActivityController::class, 'store'])->name('store');
            Route::put('/{activity}', [OsippActivityController::class, 'update'])->name('update');
            Route::delete('/{activity}', [OsippActivityController::class, 'destroy'])->name('destroy');
            Route::post('/{activity}/ajukan', [OsippActivityController::class, 'submit'])->name('submit');
            Route::post('/{activity}/verifikasi', [OsippActivityController::class, 'verify'])->name('verify');
            Route::post('/{activity}/revisi', [OsippActivityController::class, 'revision'])->name('revision');
            Route::post('/{activity}/tolak', [OsippActivityController::class, 'reject'])->name('reject');
            Route::post('/{activity}/selesai', [OsippActivityController::class, 'complete'])->name('complete');
        });

        Route::prefix('administrasi')->name('administrasi.')->group(function () {
            Route::get('/', [OsippDocumentController::class, 'index'])->name('index');
            Route::post('/', [OsippDocumentController::class, 'store'])->name('store');
            Route::put('/{document}', [OsippDocumentController::class, 'update'])->name('update');
            Route::delete('/{document}', [OsippDocumentController::class, 'destroy'])->name('destroy');
            Route::post('/{document}/ajukan', [OsippDocumentController::class, 'submit'])->name('submit');
            Route::post('/{document}/verifikasi', [OsippDocumentController::class, 'verify'])->name('verify');
            Route::post('/{document}/revisi', [OsippDocumentController::class, 'revision'])->name('revision');
            Route::post('/{document}/tolak', [OsippDocumentController::class, 'reject'])->name('reject');
            Route::get('/{document}/unduh', [OsippDocumentController::class, 'download'])->name('download');
        });

        Route::prefix('pembinaan')->name('pembinaan.')->group(function () {
            Route::get('/', [OsippCoachingController::class, 'index'])->name('index');
            Route::post('/', [OsippCoachingController::class, 'store'])->name('store');
            Route::put('/{coaching}', [OsippCoachingController::class, 'update'])->name('update');
            Route::delete('/{coaching}', [OsippCoachingController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('evaluasi')->name('evaluasi.')->group(function () {
            Route::get('/', [OsippEvaluationController::class, 'index'])->name('index');
            Route::post('/', [OsippEvaluationController::class, 'store'])->name('store');
            Route::put('/{evaluation}', [OsippEvaluationController::class, 'update'])->name('update');
            Route::delete('/{evaluation}', [OsippEvaluationController::class, 'destroy'])->name('destroy');
            Route::post('/{evaluation}/finalisasi', [OsippEvaluationController::class, 'finalize'])->name('finalize');
        });
    });
