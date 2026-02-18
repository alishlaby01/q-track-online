<?php

use App\Http\Controllers\PublicTrackingController;
use App\Http\Controllers\TechnicianDashboardController;
use App\Livewire\TrackingPage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/track/{uuid}', TrackingPage::class)->name('tracking.show');
Route::post('/track/evaluation', [PublicTrackingController::class, 'storeEvaluation'])->name('tracking.evaluation');

Route::match(['get', 'post'], '/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/admin');
})->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('technician')->name('technician.')->group(function () {
    Route::get('/', [TechnicianDashboardController::class, 'index'])->name('index');
    Route::post('/check-in', [TechnicianDashboardController::class, 'checkIn'])->name('check-in');
    Route::get('/visit/{visit}/check-out', [TechnicianDashboardController::class, 'showCheckOutForm'])->name('checkout.form');
    Route::post('/check-out', [TechnicianDashboardController::class, 'checkOut'])->name('check-out');
});
