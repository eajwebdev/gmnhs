<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::get('/end-user-property', [AppController::class, 'endUserProperty']);

Route::prefix('app')->group(function () {
    Route::post('/login', [AppController::class, 'login']);
    Route::post('/scan-qr', [AppController::class, 'scanQr']);
    Route::post('/check-invstat', [AppController::class, 'checkInvstat']);
    Route::post('/save-qr', [AppController::class, 'saveQr']);
});