<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::fallback(function () {
    return redirect("home");
});

Route::get('/', [HomeController::class, 'index'])->name('home');

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/challan', [HomeController::class, 'Challan'])->name('challan');
Route::get('/report', [HomeController::class, 'Report'])->name('report');

Route::post('/update/location', [HomeController::class, 'UpdateLocation'])->name('update-location');
Route::post('/update-key', [HomeController::class, 'updateKey'])->name('update.key');

// Test Route

Route::get('/upload', [TestController::class, 'showUploadForm']);
Route::post('/upload', [TestController::class, 'upload'])->name('upload');

Route::get('/beam', [TestController::class, 'beam']);
Route::get('/beam2', [TestController::class, 'beam2']);


Route::get('/plate-status', [HomeController::class, 'apiOnOff'])->name('plate-status');

Route::get('/settings', [HomeController::class, 'Settings'])->name('settings');



