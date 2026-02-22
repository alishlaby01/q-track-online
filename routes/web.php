<?php

use App\Http\Controllers\PublicTrackingController;
use App\Http\Controllers\TechnicianDashboardController;
use App\Livewire\TrackingPage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات الويب (Web) - Q-Track
|--------------------------------------------------------------------------
| الصفحة الرئيسية، تتبع التذكرة للعميل، تسجيل الخروج، ولوحة الفني.
*/

// الصفحة الرئيسية للموقع
Route::get('/', function () {
    return view('welcome');
});

// تتبع التذكرة: العميل يفتح الرابط /track/{uuid} لمتابعة حالته (بدون تسجيل دخول)
Route::get('/track/{uuid}', TrackingPage::class)->name('tracking.show');
// حفظ تقييم العميل بعد انتهاء الزيارة
Route::post('/track/evaluation', [PublicTrackingController::class, 'storeEvaluation'])->name('tracking.evaluation');

// تسجيل الخروج: يمسح الجلسة ويوجّه لصفحة دخول الأدمن
Route::match(['get', 'post'], '/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('filament.admin.auth.login');
})->middleware('auth')->name('logout');

// مسارات لوحة الفني (تتطلب تسجيل دخول)
Route::middleware('auth')->prefix('technician')->name('technician.')->group(function () {
    Route::get('/', [TechnicianDashboardController::class, 'index'])->name('index');
    Route::post('/on-the-way', [TechnicianDashboardController::class, 'onTheWay'])->name('on-the-way');   // في الطريق (بدون GPS)
    Route::post('/arrive', [TechnicianDashboardController::class, 'arrive'])->name('arrive');             // وصلت وبدء العمل (مع GPS)
    Route::get('/visit/{visit}/check-out', [TechnicianDashboardController::class, 'showCheckOutForm'])->name('checkout.form');
    Route::post('/check-out', [TechnicianDashboardController::class, 'checkOut'])->name('check-out'); // إرسال إنهاء الزيارة (صور + إحداثيات)
});
