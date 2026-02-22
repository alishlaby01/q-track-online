<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| مسارات الـ API - Q-Track
|--------------------------------------------------------------------------
| تستخدم للموبايل أو أي عميل خارجي عبر Bearer Token (Sanctum).
| لوحة الفني الحالية تعتمد على Web Session فقط (routes/web.php).
*/

// تسجيل الدخول عبر API (يرجع token للعميل)
Route::post('/auth/login', [AuthController::class, 'login']);

// مسارات تحتاج مصادقة: يرسل العميل Header: Authorization: Bearer {token}
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/visit/check-in', [VisitController::class, 'checkIn']);
    Route::post('/visit/check-out', [VisitController::class, 'checkOut']);
});
