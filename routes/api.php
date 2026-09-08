<?php

use App\Http\Controllers\Api\Mobile\V1\AcademicController;
use App\Http\Controllers\Api\Mobile\V1\AnnouncementController;
use App\Http\Controllers\Api\Mobile\V1\AuthController;
use App\Http\Controllers\Api\Mobile\V1\DashboardController;
use App\Http\Controllers\Api\Mobile\V1\DormitoryPermitController;
use App\Http\Controllers\Api\Mobile\V1\DormitoryReturnController;
use App\Http\Controllers\Api\Mobile\V1\DormitoryVisitController;
use App\Http\Controllers\Api\Mobile\V1\ForgotPasswordController;
use App\Http\Controllers\Api\Mobile\V1\JadwalController;
use App\Http\Controllers\Api\Mobile\V1\KalenderController;
use App\Http\Controllers\Api\Mobile\V1\MobileMahromController;
use App\Http\Controllers\Api\Mobile\V1\NotificationController;
use App\Http\Controllers\Api\Mobile\V1\PermitController;
use App\Http\Controllers\Api\Mobile\V1\RaportController;
use App\Http\Controllers\Api\Mobile\V1\SantriDataController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPredictiveApiController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetRepairVsReplaceApiController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetTcoApiController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\SarprasIntelligenceApiController;
use App\Http\Controllers\Api\Mobile\V1\StudentController;
use App\Http\Controllers\Api\Mobile\V1\TahfidzController;
use App\Http\Controllers\Api\Mobile\V1\VersionController;
use App\Http\Controllers\Api\Mobile\V1\WaliSantriController;
use App\Http\Controllers\Api\Sarpras\AssetPassportController;
use App\Http\Controllers\Api\Sarpras\RepairRequestController;
use App\Http\Controllers\Api\Sarpras\SarprasDashboardController;
use App\Http\Controllers\Api\Sarpras\StockOpnameController;
use App\Http\Controllers\Api\Sarpras\WorkOrderController;
use App\Http\Controllers\Sarpras\SarprasMobileSyncController;
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

    // ── META / VERSION / BUILD ───────────────────────────────────────────────
    Route::get('version', [VersionController::class, 'version'])->name('mobile.v1.version');
    Route::get('build', [VersionController::class, 'build'])->name('mobile.v1.build');
    Route::get('system/status', [VersionController::class, 'status'])->name('mobile.v1.system.status');
    Route::get('health', [VersionController::class, 'health'])->name('mobile.v1.health');

    // ── AUTH ─────────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('google', [AuthController::class, 'google']);
        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum');
        Route::post('logout-all', [AuthController::class, 'logoutAll'])
            ->middleware('auth:sanctum');

        // ── FORGOT / RESET PASSWORD (Sprint 2) ───────────────────────
        Route::post('forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
        Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);

        // Authenticated routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::put('me', [AuthController::class, 'updateProfile']);

            // ── SESSION MANAGEMENT (Sprint 2) ────────────────────────────────
            Route::get('sessions', [AuthController::class, 'sessions']);
            Route::patch('sessions/current', [AuthController::class, 'updateCurrentSession']);
            Route::delete('sessions/others', [AuthController::class, 'revokeOtherSessions']);
        });
    });

    // ── SANTRI ───────────────────────────────────────────────��────────────────
    Route::middleware(['auth:sanctum', 'wali.school.context', 'organization.context'])->group(function () {
        Route::get('santri', [StudentController::class, 'index']);
        Route::get('santri/verify-nik', [StudentController::class, 'verifyNik']);
        Route::get('santri/{id}', [StudentController::class, 'show']);
        Route::post('santri', [StudentController::class, 'store']);

        // ── WALI-SANTRI LINKS ─────────────────────────────────────────────────
        Route::prefix('wali-santri')->group(function () {
            Route::post('start-registration', [WaliSantriController::class, 'startRegistration']);
            Route::post('link', [WaliSantriController::class, 'link']);
            Route::post('request', [WaliSantriController::class, 'requestWaliRole']);
            Route::get('requests', [WaliSantriController::class, 'listRequests']);
            Route::put('requests/{token}', [WaliSantriController::class, 'approveReject']);
            Route::delete('{id}', [WaliSantriController::class, 'destroy']);
        });

        // ── MAHROM MANAGEMENT (Sprint 5) ──────────────────────
        Route::prefix('students/{student}/mahrom')->group(function () {
            Route::get('/', [MobileMahromController::class, 'index']);
            Route::post('/', [MobileMahromController::class, 'store']);
            Route::put('/{mahrom}', [MobileMahromController::class, 'update']);
            Route::patch('/{mahrom}', [MobileMahromController::class, 'update']);
            Route::delete('/{mahrom}', [MobileMahromController::class, 'destroy']);
        });

        // ── SANTRI DATA ───────────────────────────────────────────────────��──
        Route::prefix('santri/{id}')->group(function () {
            Route::get('attendance', [SantriDataController::class, 'attendance']);
            Route::get('attendance/history', [SantriDataController::class, 'attendanceHistory']);
            Route::get('grades', [SantriDataController::class, 'grades']);
            Route::get('violations', [SantriDataController::class, 'violations']);
            Route::get('dormitory', [SantriDataController::class, 'dormitoryInfo']);
            Route::get('dormitory-attendance', [SantriDataController::class, 'dormitoryAttendance']);
            Route::get('dormitory-violations', [SantriDataController::class, 'dormitoryViolations']);
            Route::get('health', [SantriDataController::class, 'health']);
            Route::get('tahfidz', [SantriDataController::class, 'tahfidz']);
            Route::get('classes', [SantriDataController::class, 'currentClasses']);
            Route::get('achievements', [SantriDataController::class, 'achievements']);
            Route::get('mahroms', [SantriDataController::class, 'mahroms']);
        });

        // ── NOTIFICATIONS ──────────────────────────────────────────────────
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::put('notifications/{id}/read', [NotificationController::class, 'markRead']);

        // ── DORMITORY PERMIT ─────────────────────────────────────────────
        Route::get('dormitory-permits', [DormitoryPermitController::class, 'index']);
        Route::post('dormitory/permit', [DormitoryPermitController::class, 'store']);

        // ── DORMITORY VISIT (Sprint 4) ─────────────────────────────────────
        Route::get('dormitory/visits', [DormitoryVisitController::class, 'index']);
        Route::post('dormitory/visit', [DormitoryVisitController::class, 'store']);
        Route::get('dormitory/visits/{id}', [DormitoryVisitController::class, 'show']);
        Route::patch('dormitory/visits/{id}/check-in', [DormitoryVisitController::class, 'checkIn']);
        Route::patch('dormitory/visits/{id}/check-out', [DormitoryVisitController::class, 'checkOut']);

        // ── DORMITORY RETURN (Sprint 4) ────────────────────────────────────
        Route::get('dormitory/returns', [DormitoryReturnController::class, 'index']);
        Route::post('dormitory/return', [DormitoryReturnController::class, 'store']);

        // ── DASHBOARD ──────────────────────────────────────────────────────
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('attendance', [DashboardController::class, 'attendance']);
        });

        // ── ANNOUNCEMENTS (Sprint 3) ──────────────────────────────────────
        Route::get('announcements', [AnnouncementController::class, 'index']);
        Route::get('announcements/{id}', [AnnouncementController::class, 'show']);

        // ── JADWAL KBM (Sprint 3) ─────────────────────────────────────────
        Route::prefix('jadwal')->group(function () {
            Route::get('/', [JadwalController::class, 'index']);
            Route::get('week', [JadwalController::class, 'week']);
        });

        // ── IZIN / SAKIT (Sprint 3) ───────────────────────────────────────
        Route::prefix('permit')->group(function () {
            Route::post('request', [PermitController::class, 'request']);
            Route::get('/', [PermitController::class, 'myPermits']);
        });

        // ── RAPORT (Sprint 3) ─────────────────────────────────────────────
        Route::prefix('raport')->group(function () {
            Route::get('status', [RaportController::class, 'status']);
            Route::get('{student_id}', [RaportController::class, 'show']);
        });

        // ── TAHFIDZ (Sprint 3) ────────────────────────────────────────────
        Route::prefix('tahfidz')->group(function () {
            Route::get('/', [TahfidzController::class, 'index']);
            Route::get('progress', [TahfidzController::class, 'progress']);
        });

        // ── KALENDER KALDIK (Sprint 3) ────────────────────────────────────
        Route::get('kalender', [KalenderController::class, 'index']);

        // ── ACADEMIC SUMMARY (Sprint 3) ───────────────────────────────────
        Route::prefix('academic')->group(function () {
            Route::get('summary', [AcademicController::class, 'summary']);
            Route::get('rewards', [AcademicController::class, 'rewards']);
        });

        // ── NOTIFICATIONS — mark-all-read (Sprint 3) ──────────────────────
        Route::patch('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
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
        AssetPassportApiController::class,
        'show',
    ])->name('api.sarpras.passport');

    // ── DAMAGE REPORT SUBMISSION (from mobile) ───────────────────────────
    Route::post('damage-report', [
        AssetPassportApiController::class,
        'submitDamageReport',
    ])->name('api.sarpras.damage-report');

    // ── REPAIR REQUEST WORKFLOW ──────────────────────────────────────────
    Route::prefix('repairs')->group(function () {
        Route::get('/', [
            AssetPassportApiController::class,
            'listRepairs',
        ])->name('api.sarpras.repairs.index');

        Route::post('{repairId}/verify', [
            AssetPassportApiController::class,
            'verifyRepair',
        ])->name('api.sarpras.repairs.verify');

        Route::post('{repairId}/generate-work-order', [
            AssetPassportApiController::class,
            'generateWorkOrder',
        ])->name('api.sarpras.repairs.generate-wo');

        Route::post('{repairId}/pic-verify', [
            AssetPassportApiController::class,
            'verifyByPic',
        ])->name('api.sarpras.repairs.pic-verify');
    });

    // ── WORK ORDER WORKFLOW ──────────────────────────────────────────────
    Route::prefix('work-orders')->group(function () {
        Route::post('{orderId}/assign', [
            AssetPassportApiController::class,
            'assignTechnician',
        ])->name('api.sarpras.work-orders.assign');

        Route::post('{orderId}/accept', [
            AssetPassportApiController::class,
            'acceptOrder',
        ])->name('api.sarpras.work-orders.accept');

        Route::post('{orderId}/start', [
            AssetPassportApiController::class,
            'startWork',
        ])->name('api.sarpras.work-orders.start');

        Route::post('{orderId}/complete', [
            AssetPassportApiController::class,
            'completeOrder',
        ])->name('api.sarpras.work-orders.complete');
    });

    // ── ASSET INTELLIGENCE (v2 mobile) ─────────────────────────────────────
    Route::prefix('tco')->group(function () {
        Route::get('{assetId}', [AssetTcoApiController::class, 'show'])->name('api.sarpras.tco.show');
        Route::post('compare', [AssetTcoApiController::class, 'compare'])->name('api.sarpras.tco.compare');
    });

    Route::prefix('repair-vs-replace')->group(function () {
        Route::get('{assetId}', [AssetRepairVsReplaceApiController::class, 'show'])->name('api.sarpras.rvr.show');
        Route::get('priority/list', [AssetRepairVsReplaceApiController::class, 'listHighPriority'])->name('api.sarpras.rvr.priority');
    });

    Route::prefix('predictive')->group(function () {
        Route::get('{assetId}', [AssetPredictiveApiController::class, 'show'])->name('api.sarpras.predictive.show');
        Route::get('high-risk/list', [AssetPredictiveApiController::class, 'highRisk'])->name('api.sarpras.predictive.high_risk');
    });

    Route::get('intelligence/summary', [SarprasIntelligenceApiController::class, 'summary'])->name('api.sarpras.intelligence.summary');
});

// ── SARPRAS WEB API v2 ─────────────────────────────────────────────────────────
// Uses App\Http\Controllers\Api\Sarpras\* — web/scanner/admin facing
Route::middleware(['auth:sanctum', 'organization.context'])->prefix('sarpras/v2')->group(function () {

    // ── ENTERPRISE DASHBOARD ─────────────────────────────────────────────
    Route::prefix('dashboard')->group(function () {
        Route::get('overview', [SarprasDashboardController::class, 'overview']);
        Route::get('activity-feed', [SarprasDashboardController::class, 'activityFeed']);
        Route::get('category-heatmap', [SarprasDashboardController::class, 'categoryHeatmap']);
        Route::get('cost-leak-report', [SarprasDashboardController::class, 'costLeakReport']);
    });

    // ── ASSET PASSPORT (WEB) ─────────────────────────────────────────────
    Route::prefix('passport')->group(function () {
        Route::post('qr-scan', [AssetPassportController::class, 'qrScan']);
        Route::get('public/{token}', [AssetPassportController::class, 'qrPublicLookup']);
        Route::get('scan-history', [AssetPassportController::class, 'scanHistory']);
        Route::get('{assetId}', [AssetPassportController::class, 'passportDetail']);
        Route::get('{assetId}/cost-history', [AssetPassportController::class, 'costHistory']);
    });

    // ── REPAIR REQUESTS ──────────────────────────────────────────────────
    Route::prefix('repairs')->group(function () {
        Route::get('/', [RepairRequestController::class, 'index']);
        Route::post('/', [RepairRequestController::class, 'submit']);
        Route::get('{id}', [RepairRequestController::class, 'show']);
        Route::post('{id}/review', [RepairRequestController::class, 'review']);
    });

    // ── WORK ORDERS ──────────────────────────────────────────────────────
    Route::prefix('work-orders')->group(function () {
        Route::get('/', [WorkOrderController::class, 'index']);
        Route::post('/', [WorkOrderController::class, 'generateFromRepair']);
        Route::get('stats', [WorkOrderController::class, 'stats']);
        Route::get('{id}', [WorkOrderController::class, 'show']);
        Route::post('{id}/transition', [WorkOrderController::class, 'transition']);
        Route::post('{id}/progress', [WorkOrderController::class, 'addProgress']);
        Route::post('{id}/costs', [WorkOrderController::class, 'recordCost']);
        Route::post('{id}/spareparts', [WorkOrderController::class, 'recordSparePart']);
    });

    // ── STOCK OPNAME ─────────────────────────────────────────────────────
    Route::prefix('opname')->group(function () {
        Route::get('sessions', [StockOpnameController::class, 'sessions']);
        Route::post('sessions', [StockOpnameController::class, 'createSession']);
        Route::get('sessions/{id}', [StockOpnameController::class, 'showSession']);
        Route::post('sessions/{id}/close', [StockOpnameController::class, 'closeSession']);
        Route::post('sessions/{id}/variance', [StockOpnameController::class, 'varianceReport']);
        Route::post('sessions/{id}/items/{itemId}', [StockOpnameController::class, 'recordObservation']);
        Route::post('sessions/{id}/qr-scan', [StockOpnameController::class, 'qrScan']);
    });

    // ── OFFLINE MOBILE SYNC (batch operations) ──────────────────────
    Route::prefix('sarpras/sync')->group(function () {
        Route::get('pull', [SarprasMobileSyncController::class, 'pull']);
        Route::post('push', [SarprasMobileSyncController::class, 'push']);
    });
});
