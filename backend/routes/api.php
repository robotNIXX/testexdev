<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\TestController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/recent-operations', [OperationController::class, 'recent']);
    Route::get('/operations', [OperationController::class, 'index']);
    Route::get('/operations/statistics', [OperationController::class, 'statistics']);
    Route::get('/operations/by-date-range', [OperationController::class, 'byDateRange']);
    Route::get('/operations/monthly-summary', [OperationController::class, 'monthlySummary']);
});

Route::get('/test', [TestController::class, 'test']);
Route::get('/health', [TestController::class, 'health']); 