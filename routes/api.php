<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UrlController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['api'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/getaccount', [AuthController::class, 'getaccount']);

    Route::controller(PriceController::class)->prefix('prices')->group(function () {
        Route::get('/', 'index');
        Route::post('/create', 'create');
        Route::post('/update', 'update');
    });

    Route::controller(ServiceController::class)->prefix('services')->group(function () {
        Route::get('', 'index');
        Route::post('/create', 'create');
        Route::post('/update', 'update');
        Route::post('/delete', 'destroy');
    });

    Route::controller(ClientController::class)->prefix('clients')->group(function () {
        Route::get('', 'index');
        Route::post('/create', 'create');
        Route::post('/update', 'update');
        Route::get('/birthdays', 'getBirthdayClient');
    });
    Route::controller(ShiftController::class)->prefix('shifts')->group(function () {
        Route::get('', 'index');
        Route::post('/create', 'create');
        Route::post('/update', 'update');
        Route::post('/delete', 'destroy');
        Route::post('/updateStatus', 'updateStatus');
        Route::get('/emailShifts', 'sendEmailsForNewShiftsInFiveMinutes');
        Route::get('/reminderShifts', 'getShiftsForTomorrowAndSendReminderEmail');
        Route::get('/reports', 'reportsMonth');
    });

    Route::controller(UserController::class)->prefix('users')->group(function () {
        Route::get('', 'index');
        Route::post('/create', 'create');
        Route::get('getShiftToUsers', 'usersToShift');
    });

    Route::controller(UrlController::class)->prefix('urls')->group(function () {
        Route::get('', 'index');
        Route::post('/create', 'create');
        Route::post('/update', 'update');
    });
});
