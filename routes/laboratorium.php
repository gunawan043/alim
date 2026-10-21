<?php

use App\Http\Controllers\LabController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('{userId}/laboratorium/pengajuan')->name('lab.request.')->group(function () {
    Route::get('/', [LabController::class, 'requestIndex'])->name('index');
    Route::post('/', [LabController::class, 'requestStore'])->name('store');
});


/*
|--------------------------------------------------------------------------
| LABORATORIUM IPA
|--------------------------------------------------------------------------
| Modul terpisah dari Sarpras. Inventaris memakai master `assets` yang ada.
| Penugasan memakai other_teacher_tasks (school/year).
*/
Route::middleware(['auth', 'lab.access'])->prefix('{userId}/laboratorium')->name('lab.')->group(function () {
    Route::get('/', [LabController::class, 'index'])->name('index');
    Route::get('/inventaris', [LabController::class, 'index'])->name('inventory');
    Route::get('/inventaris/cetak', [LabController::class, 'inventoryPrint'])->name('inventory.print');
    Route::post('/profil', [LabController::class, 'profileStore'])->name('profile.store');
    Route::post('/inventaris', [LabController::class, 'inventoryStore'])->name('inventory.store');
    Route::get('/stok', [LabController::class, 'batchIndex'])->name('batches.index');
    Route::post('/stok', [LabController::class, 'batchStore'])->name('batches.store');
    Route::post('/stok/{batch}/pakai', [LabController::class, 'batchConsume'])->name('batches.consume');

    Route::get('/pemeriksaan', [LabController::class, 'inspectionIndex'])->name('inspection.index');
    Route::post('/pemeriksaan', [LabController::class, 'inspectionStore'])->name('inspection.store');
    Route::get('/pemeriksaan/{inspection}/cetak', [LabController::class, 'inspectionPrint'])->name('inspection.print');

    Route::get('/kalibrasi', [LabController::class, 'calibrationIndex'])->name('calibration.index');
    Route::post('/kalibrasi', [LabController::class, 'calibrationStore'])->name('calibration.store');
    Route::get('/kalibrasi/cetak', [LabController::class, 'calibrationPrint'])->name('calibration.print');
    Route::get('/kalibrasi/{calibration}/sertifikat', [LabController::class, 'calibrationDownload'])->name('calibration.download');

    Route::get('/peminjaman', [LabController::class, 'loanIndex'])->name('loan.index');
    Route::post('/peminjaman', [LabController::class, 'loanStore'])->name('loan.store');
    Route::post('/peminjaman/{loan}/setujui', [LabController::class, 'loanApprove'])->name('loan.approve');
    Route::post('/peminjaman/{loan}/serah', [LabController::class, 'loanHandover'])->name('loan.handover');
    Route::post('/peminjaman/{loan}/kembali', [LabController::class, 'loanReturn'])->name('loan.return');
    Route::get('/peminjaman/{loan}/cetak', [LabController::class, 'loanPrint'])->name('loan.print');

    Route::get('/kegiatan', [LabController::class, 'activityIndex'])->name('activity.index');
    Route::post('/kegiatan', [LabController::class, 'activityStore'])->name('activity.store');
    Route::get('/kegiatan/{activity}', [LabController::class, 'activityShow'])->name('activity.show');
    Route::post('/kegiatan/{activity}/berita-acara', [LabController::class, 'reportStore'])->name('activity.report.store');
    Route::get('/kegiatan/{activity}/berita-acara/cetak', [LabController::class, 'reportPrint'])->name('activity.report.print');

    Route::get('/izin', [LabController::class, 'usageIndex'])->name('usage.index');
    Route::post('/izin', [LabController::class, 'usageStore'])->name('usage.store');
    Route::post('/izin/{usage}/setujui', [LabController::class, 'usageApprove'])->name('usage.approve');
    Route::post('/izin/{usage}/tolak', [LabController::class, 'usageReject'])->name('usage.reject');

    Route::get('/jadwal', [LabController::class, 'scheduleIndex'])->name('schedule.index');
    Route::post('/jadwal', [LabController::class, 'scheduleStore'])->name('schedule.store');
    Route::get('/jadwal/cetak', [LabController::class, 'schedulePrint'])->name('schedule.print');

    Route::get('/keselamatan', [LabController::class, 'safetyIndex'])->name('safety.index');
    Route::post('/keselamatan', [LabController::class, 'safetyStore'])->name('safety.store');
    Route::get('/keselamatan/{inspection}/cetak', [LabController::class, 'safetyPrint'])->name('safety.print');

    Route::get('/insiden', [LabController::class, 'incidentIndex'])->name('incident.index');
    Route::post('/insiden', [LabController::class, 'incidentStore'])->name('incident.store');
    Route::get('/insiden/{incident}', [LabController::class, 'incidentShow'])->name('incident.show');
    Route::get('/insiden/{incident}/cetak', [LabController::class, 'incidentPrint'])->name('incident.print');

    Route::get('/penghapusan', [LabController::class, 'disposalIndex'])->name('disposal.index');
    Route::post('/penghapusan', [LabController::class, 'disposalStore'])->name('disposal.store');
    Route::post('/penghapusan/{disposal}/setujui', [LabController::class, 'disposalApprove'])->name('disposal.approve');
    Route::post('/penghapusan/{disposal}/tolak', [LabController::class, 'disposalReject'])->name('disposal.reject');
    Route::get('/penghapusan/{disposal}/cetak', [LabController::class, 'disposalPrint'])->name('disposal.print');

    Route::get('/evaluasi', [LabController::class, 'evaluationIndex'])->name('evaluation.index');
    Route::post('/evaluasi', [LabController::class, 'evaluationStore'])->name('evaluation.store');
});
