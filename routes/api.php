<?php

use App\Http\Controllers\WilayahController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Public + Auth
|--------------------------------------------------------------------------
*/

// ── PUBLIC: WILAYAH (Indonesia provinces/cities/districts/villages) ──────────
// Data ini tidak sensitif, tidak perlu login
Route::get('wilayah/provinces', [WilayahController::class, 'provinces'])->name('wilayah.provinces');
Route::get('wilayah/cities/{provinceCode}', [WilayahController::class, 'cities'])->name('wilayah.cities');
Route::get('wilayah/districts/{cityCode}', [WilayahController::class, 'districts'])->name('wilayah.districts');
Route::get('wilayah/villages/{districtCode}', [WilayahController::class, 'villages'])->name('wilayah.villages');

// ── MOBILE WALI API v1 ───────────────────────────────────────────────────────

Route::prefix('mobile/v1')->group(function () {

    // ── AUTH ─────────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'register']);
        Route::post('login', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'login']);
        Route::post('google', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'google']);
        Route::post('logout', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'logout'])
            ->middleware('auth:sanctum');
        Route::post('logout-all', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'logoutAll'])
            ->middleware('auth:sanctum');

        // ── FORGOT / RESET PASSWORD (Sprint 2) ───────────────────────
        Route::post('forgot-password', [App\Http\Controllers\Api\Mobile\V1\ForgotPasswordController::class, 'forgotPassword']);
        Route::post('reset-password', [App\Http\Controllers\Api\Mobile\V1\ForgotPasswordController::class, 'resetPassword']);

        // Authenticated routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'me']);
            Route::put('me', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'updateProfile']);

            // ── SESSION MANAGEMENT (Sprint 2) ────────────────────────────────
            Route::get('sessions', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'sessions']);
            Route::patch('sessions/current', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'updateCurrentSession']);
            Route::delete('sessions/others', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'revokeOtherSessions']);
        });
    });

    // ── SANTRI ───────────────────────────────────────────────��────────────────
    Route::middleware(['auth:sanctum', 'wali.school.context', 'organization.context'])->group(function () {
        Route::get('santri', [App\Http\Controllers\Api\Mobile\V1\StudentController::class, 'index']);
        Route::get('santri/verify-nik', [App\Http\Controllers\Api\Mobile\V1\StudentController::class, 'verifyNik']);
        Route::get('santri/{id}', [App\Http\Controllers\Api\Mobile\V1\StudentController::class, 'show']);
        Route::post('santri', [App\Http\Controllers\Api\Mobile\V1\StudentController::class, 'store']);

        // ── WALI-SANTRI LINKS ─────────────────────────────────────────────────
        Route::prefix('wali-santri')->group(function () {
            Route::post('start-registration', [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'startRegistration']);
            Route::post('link', [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'link']);
            Route::post('request', [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'requestWaliRole']);
            Route::get('requests', [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'listRequests']);
            Route::put('requests/{token}', [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'approveReject']);
            Route::delete('{id}', [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'destroy']);
        });

        // ── SANTRI DATA ───────────────────────────────────────────────────��──
        Route::prefix('santri/{id}')->group(function () {
            Route::get('attendance', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'attendance']);
            Route::get('attendance/history', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'attendanceHistory']);
            Route::get('grades', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'grades']);
            Route::get('violations', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'violations']);
            Route::get('dormitory', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'dormitoryInfo']);
            Route::get('dormitory-attendance', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'dormitoryAttendance']);
            Route::get('dormitory-violations', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'dormitoryViolations']);
            Route::get('health', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'health']);
            Route::get('tahfidz', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'tahfidz']);
            Route::get('classes', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'currentClasses']);
            Route::get('achievements', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'achievements']);
            Route::get('mahroms', [App\Http\Controllers\Api\Mobile\V1\SantriDataController::class, 'mahroms']);
        });

        // ── NOTIFICATIONS ──────────────────────────────────────────────────
        Route::get('notifications', [App\Http\Controllers\Api\Mobile\V1\NotificationController::class, 'index']);
        Route::put('notifications/{id}/read', [App\Http\Controllers\Api\Mobile\V1\NotificationController::class, 'markRead']);

        // ── DORMITORY PERMIT ─────────────────────────────────────────────
        Route::get('dormitory-permits', [App\Http\Controllers\Api\Mobile\V1\DormitoryPermitController::class, 'index']);
        Route::post('dormitory/permit', [App\Http\Controllers\Api\Mobile\V1\DormitoryPermitController::class, 'store']);

        // ── DORMITORY VISIT (Sprint 4) ─────────────────────────────────────
        Route::get('dormitory/visits', [App\Http\Controllers\Api\Mobile\V1\DormitoryVisitController::class, 'index']);
        Route::post('dormitory/visit', [App\Http\Controllers\Api\Mobile\V1\DormitoryVisitController::class, 'store']);
        Route::get('dormitory/visits/{id}', [App\Http\Controllers\Api\Mobile\V1\DormitoryVisitController::class, 'show']);
        Route::patch('dormitory/visits/{id}/check-in', [App\Http\Controllers\Api\Mobile\V1\DormitoryVisitController::class, 'checkIn']);
        Route::patch('dormitory/visits/{id}/check-out', [App\Http\Controllers\Api\Mobile\V1\DormitoryVisitController::class, 'checkOut']);

        // ── DORMITORY RETURN (Sprint 4) ────────────────────────────────────
        Route::get('dormitory/returns', [App\Http\Controllers\Api\Mobile\V1\DormitoryReturnController::class, 'index']);
        Route::post('dormitory/return', [App\Http\Controllers\Api\Mobile\V1\DormitoryReturnController::class, 'store']);

        // ── DASHBOARD ──────────────────────────────────────────────────────
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Mobile\V1\DashboardController::class, 'index']);
            Route::get('attendance', [App\Http\Controllers\Api\Mobile\V1\DashboardController::class, 'attendance']);
        });

        // ── ANNOUNCEMENTS (Sprint 3) ──────────────────────────────────────
        Route::get('announcements', [App\Http\Controllers\Api\Mobile\V1\AnnouncementController::class, 'index']);
        Route::get('announcements/{id}', [App\Http\Controllers\Api\Mobile\V1\AnnouncementController::class, 'show']);

        // ── JADWAL KBM (Sprint 3) ─────────────────────────────────────────
        Route::prefix('jadwal')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Mobile\V1\JadwalController::class, 'index']);
            Route::get('week', [App\Http\Controllers\Api\Mobile\V1\JadwalController::class, 'week']);
        });

        // ── IZIN / SAKIT (Sprint 3) ───────────────────────────────────────
        Route::prefix('permit')->group(function () {
            Route::post('request', [App\Http\Controllers\Api\Mobile\V1\PermitController::class, 'request']);
            Route::get('/', [App\Http\Controllers\Api\Mobile\V1\PermitController::class, 'myPermits']);
        });

        // ── RAPORT (Sprint 3) ─────────────────────────────────────────────
        Route::prefix('raport')->group(function () {
            Route::get('status', [App\Http\Controllers\Api\Mobile\V1\RaportController::class, 'status']);
            Route::get('{student_id}', [App\Http\Controllers\Api\Mobile\V1\RaportController::class, 'show']);
        });

        // ── TAHFIDZ (Sprint 3) ────────────────────────────────────────────
        Route::prefix('tahfidz')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Mobile\V1\TahfidzController::class, 'index']);
            Route::get('progress', [App\Http\Controllers\Api\Mobile\V1\TahfidzController::class, 'progress']);
        });

        // ── KALENDER KALDIK (Sprint 3) ────────────────────────────────────
        Route::get('kalender', [App\Http\Controllers\Api\Mobile\V1\KalenderController::class, 'index']);

        // ── ACADEMIC SUMMARY (Sprint 3) ───────────────────────────────────
        Route::prefix('academic')->group(function () {
            Route::get('summary', [App\Http\Controllers\Api\Mobile\V1\AcademicController::class, 'summary']);
            Route::get('rewards', [App\Http\Controllers\Api\Mobile\V1\AcademicController::class, 'rewards']);
        });

        // ── NOTIFICATIONS — mark-all-read (Sprint 3) ──────────────────────
        Route::patch('notifications/mark-all-read', [App\Http\Controllers\Api\Mobile\V1\NotificationController::class, 'markAllRead']);
    });
});

// ── AUTHENTICATED ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'organization.context'])->get('/user', function (Request $request) {
    return $request->user();
});

// ── SARPRAS / ASSET MOBILE API v1 ────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'organization.context'])->prefix('sarpras')->group(function () {

    // ── ASSET PASSPORT ─────────────────────────────────────────────────────
    // Scan QR code or enter asset code → full passport with lifecycle data
    Route::get('passport/{lookup}', [
        \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
        'show',
    ])->name('api.sarpras.passport');

    // ── DAMAGE REPORT SUBMISSION (from mobile) ───────────────────────────
    Route::post('damage-report', [
        \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
        'submitDamageReport',
    ])->name('api.sarpras.damage-report');

    // ── REPAIR REQUEST WORKFLOW ──────────────────────────────────────────
    Route::prefix('repairs')->group(function () {
        Route::get('/', [
            \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
            'listRepairs',
        ])->name('api.sarpras.repairs.index');

        Route::post('{repairId}/verify', [
            \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
            'verifyRepair',
        ])->name('api.sarpras.repairs.verify');

        Route::post('{repairId}/generate-work-order', [
            \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
            'generateWorkOrder',
        ])->name('api.sarpras.repairs.generate-wo');

        Route::post('{repairId}/pic-verify', [
            \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
            'verifyByPic',
        ])->name('api.sarpras.repairs.pic-verify');
    });

    // ── WORK ORDER WORKFLOW ──────────────────────────────────────────────
    Route::prefix('work-orders')->group(function () {
        Route::post('{orderId}/assign', [
            \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
            'assignTechnician',
        ])->name('api.sarpras.work-orders.assign');

        Route::post('{orderId}/accept', [
            \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
            'acceptOrder',
        ])->name('api.sarpras.work-orders.accept');

        Route::post('{orderId}/start', [
            \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
            'startWork',
        ])->name('api.sarpras.work-orders.start');

        Route::post('{orderId}/complete', [
            \App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController::class,
            'completeOrder',
        ])->name('api.sarpras.work-orders.complete');
    });
});

// ── SARPRAS WEB API v2 ─────────────────────────────────────────────────────────
// Uses App\Http\Controllers\Api\Sarpras\* — web/scanner/admin facing
Route::middleware(['auth:sanctum', 'organization.context'])->prefix('sarpras/v2')->group(function () {

    // ── ENTERPRISE DASHBOARD ─────────────────────────────────────────────
    Route::prefix('dashboard')->group(function () {
        Route::get('overview', [\App\Http\Controllers\Api\Sarpras\SarprasDashboardController::class, 'overview']);
        Route::get('activity-feed', [\App\Http\Controllers\Api\Sarpras\SarprasDashboardController::class, 'activityFeed']);
        Route::get('category-heatmap', [\App\Http\Controllers\Api\Sarpras\SarprasDashboardController::class, 'categoryHeatmap']);
        Route::get('cost-leak-report', [\App\Http\Controllers\Api\Sarpras\SarprasDashboardController::class, 'costLeakReport']);
    });

    // ── ASSET PASSPORT (WEB) ─────────────────────────────────────────────
    Route::prefix('passport')->group(function () {
        Route::post('qr-scan', [\App\Http\Controllers\Api\Sarpras\AssetPassportController::class, 'qrScan']);
        Route::get('public/{token}', [\App\Http\Controllers\Api\Sarpras\AssetPassportController::class, 'qrPublicLookup']);
        Route::get('scan-history', [\App\Http\Controllers\Api\Sarpras\AssetPassportController::class, 'scanHistory']);
        Route::get('{assetId}', [\App\Http\Controllers\Api\Sarpras\AssetPassportController::class, 'passportDetail']);
        Route::get('{assetId}/cost-history', [\App\Http\Controllers\Api\Sarpras\AssetPassportController::class, 'costHistory']);
    });

    // ── REPAIR REQUESTS ──────────────────────────────────────────────────
    Route::prefix('repairs')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Sarpras\RepairRequestController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Sarpras\RepairRequestController::class, 'submit']);
        Route::get('{id}', [\App\Http\Controllers\Api\Sarpras\RepairRequestController::class, 'show']);
        Route::post('{id}/review', [\App\Http\Controllers\Api\Sarpras\RepairRequestController::class, 'review']);
    });

    // ── WORK ORDERS ──────────────────────────────────────────────────────
    Route::prefix('work-orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Sarpras\WorkOrderController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Sarpras\WorkOrderController::class, 'generateFromRepair']);
        Route::get('stats', [\App\Http\Controllers\Api\Sarpras\WorkOrderController::class, 'stats']);
        Route::get('{id}', [\App\Http\Controllers\Api\Sarpras\WorkOrderController::class, 'show']);
        Route::post('{id}/transition', [\App\Http\Controllers\Api\Sarpras\WorkOrderController::class, 'transition']);
        Route::post('{id}/progress', [\App\Http\Controllers\Api\Sarpras\WorkOrderController::class, 'addProgress']);
        Route::post('{id}/costs', [\App\Http\Controllers\Api\Sarpras\WorkOrderController::class, 'recordCost']);
        Route::post('{id}/spareparts', [\App\Http\Controllers\Api\Sarpras\WorkOrderController::class, 'recordSparePart']);
    });

    // ── STOCK OPNAME ─────────────────────────────────────────────────────
    Route::prefix('opname')->group(function () {
        Route::get('sessions', [\App\Http\Controllers\Api\Sarpras\StockOpnameController::class, 'sessions']);
        Route::post('sessions', [\App\Http\Controllers\Api\Sarpras\StockOpnameController::class, 'createSession']);
        Route::get('sessions/{id}', [\App\Http\Controllers\Api\Sarpras\StockOpnameController::class, 'showSession']);
        Route::post('sessions/{id}/close', [\App\Http\Controllers\Api\Sarpras\StockOpnameController::class, 'closeSession']);
        Route::post('sessions/{id}/variance', [\App\Http\Controllers\Api\Sarpras\StockOpnameController::class, 'varianceReport']);
        Route::post('sessions/{id}/items/{itemId}', [\App\Http\Controllers\Api\Sarpras\StockOpnameController::class, 'recordObservation']);
        Route::post('sessions/{id}/qr-scan', [\App\Http\Controllers\Api\Sarpras\StockOpnameController::class, 'qrScan']);
    });

    // ── OFFLINE MOBILE SYNC (batch operations) ──────────────────────
    Route::prefix('sarpras/sync')->group(function () {
        Route::get('pull', [\App\Http\Controllers\Sarpras\SarprasMobileSyncController::class, 'pull']);
        Route::post('push', [\App\Http\Controllers\Sarpras\SarprasMobileSyncController::class, 'push']);
    });
});
