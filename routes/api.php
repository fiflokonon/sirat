<?php

use App\Http\Controllers\Api\User\AuthController;
use App\Http\Controllers\Api\User\PaymentController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/init-user', [UserController::class, 'initUser']);
Route::post('/users/{unique_id}/recharge', [PaymentController::class, 'recharge']);
Route::get('/users/{unique_id}', [UserController::class, 'infos']);
Route::get('/users/{unique_id}/payments', [UserController::class, 'userPayments']);
Route::post('/users/{unique_id}/scan', [UserController::class, 'scan'])->middleware('auth:sanctum');
Route::post('/users/{unique_id}/new-scan', [UserController::class, 'newScan'])->middleware('auth:sanctum');
Route::get('/agents/scans', [UserController::class, 'scans'])->middleware('auth:sanctum');
Route::put('/payment-status', [PaymentController::class, 'checking']);
Route::put('/new-payment-status', [PaymentController::class, 'newChecking'])->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/callback', function () {
    return "Paiement retour";
})->name('callback');
