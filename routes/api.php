<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\WithdrawalController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/transfer', [TransferController::class, 'transfer']);
    Route::post('/kyc', [AuthController::class, 'submitKyc']);
    Route::post('/set-pin', [AuthController::class, 'setPin']);
     Route::post('/withdraw', [WithdrawalController::class, 'withdraw']);
});