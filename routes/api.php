<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\TicketController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::fallback(function () {
    return response()->json([
        "message" => "URL not found",
        "code" => 404
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/zip/analyser', [FileController::class, 'extractZipFile'])->name('home');
Route::get('/delete/unzip', [FileController::class, 'deleteUnzipFile'])->name('home');

Route::get('/analyser/data', [FileController::class, 'LatestRecord'])->name('latest-record');





// All Pending Tickets (10 Tickets at a time)
Route::get(
    'tickets/location/{locationID}/date-from/{m_DateFrom}/date-to/{m_DateTo}',
    [TicketController::class, 'allTicket']
)->name('tickets.location');

// Ticket Detail
Route::get(
    'tickets/{ticketID}',
    [TicketController::class, 'ticketDetail']
)->name('tickets.details');


// Update Ticket Download Status
Route::get(
    'tickets/{ticketID}/export-status/{status}',
    [TicketController::class, 'ticketStatusUpdate']
)->name('tickets.status.update');


// Download Main Image
Route::get(
    'tickets/image/{ticketID}',
    [TicketController::class, 'DownloadMainImage']
)->name('tickets.main.image.download');


// Download Beam Image
Route::get(
    'tickets/violation-image/{hashImg}',
    [TicketController::class, 'DownloadBeamImage']
)->name('tickets.beam.image.download');

Route::get(
    'img',
    [TicketController::class, 'IMG']
)->name('beam.img');


// R&D
Route::match(
    ['get', 'post'],
    'file/n',
    [FileController::class, 'uploadFiles']
)->name('file.n.image');

Route::match(
    ['get', 'post'],
    'get/handleData',
    [FileController::class, 'handleData']
)->name('get.n.data');
