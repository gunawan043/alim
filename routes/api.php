<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WilayahController;

/*
|--------------------------------------------------------------------------
| API Routes — Public + Auth
|--------------------------------------------------------------------------
*/

// ── PUBLIC: WILAYAH (Indonesia provinces/cities/districts/villages) ──────────
// Data ini tidak sensitif, tidak perlu login
Route::get('wilayah/provinces',               [WilayahController::class, 'provinces'])->name('wilayah.provinces');
Route::get('wilayah/cities/{provinceCode}',   [WilayahController::class, 'cities'])->name('wilayah.cities');
Route::get('wilayah/districts/{cityCode}',    [WilayahController::class, 'districts'])->name('wilayah.districts');
Route::get('wilayah/villages/{districtCode}', [WilayahController::class, 'villages'])->name('wilayah.villages');

// ── MOBILE WALI API v1 ───────────────────────────────────────────────────────

Route::prefix('mobile/v1')->group(function () {

    // ── AUTH ─────────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'register']);
        Route::post('login',    [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'login']);
        Route::post('google',    [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'google']);
        Route::post('logout',   [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'logout'])
            ->middleware('auth:sanctum');

        // Authenticated routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me',    [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'me']);
            Route::put('me',    [App\Http\Controllers\Api\Mobile\V1\AuthController::class, 'updateProfile']);
        });
    });

    // ── SANTRI ────────────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('santri',                                  [App\Http\Controllers\Api\Mobile\V1\StudentController::class, 'index']);
        Route::get('santri/verify-nik',                       [App\Http\Controllers\Api\Mobile\V1\StudentController::class, 'verifyNik']);
        Route::get('santri/{id}',                             [App\Http\Controllers\Api\Mobile\V1\StudentController::class, 'show']);
        Route::post('santri',                                 [App\Http\Controllers\Api\Mobile\V1\StudentController::class, 'store']);

        // ── WALI-SANTRI LINKS ─────────────────────────────────────────────────
        Route::prefix('wali-santri')->group(function () {
            Route::post('link',    [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'link']);
            Route::post('request', [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'requestWaliRole']);
            Route::get('requests', [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'listRequests']);
            Route::put('requests/{token}', [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'approveReject']);
            Route::delete('{id}',   [App\Http\Controllers\Api\Mobile\V1\WaliSantriController::class, 'destroy']);
        });

        // ── DASHBOARD ────────────────────────────────────────────────────────
        Route::prefix('dashboard')->group(function () {
            Route::get('/',                  [App\Http\Controllers\Api\Mobile\V1\DashboardController::class, 'index']);
            Route::get('attendance',         [App\Http\Controllers\Api\Mobile\V1\DashboardController::class, 'attendance']);
        });
    });
});

// ── AUTHENTICATED ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
