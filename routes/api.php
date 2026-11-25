<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OrderApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Master & Pencarian Cepat
    Route::get('/services', [OrderApiController::class, 'services']);
    Route::get('/customers/search', [OrderApiController::class, 'searchCustomers']);

    // Transaksi Inti & Kalkulasi
    Route::get('/orders', [OrderApiController::class, 'index']);
    Route::post('/orders/calculate', [OrderApiController::class, 'calculate']);
    Route::post('/orders', [OrderApiController::class, 'store']);
    Route::patch('/orders/{order}/status', [OrderApiController::class, 'updateStatus']);
    Route::post('/orders/{order}/resend-wa', [OrderApiController::class, 'resendWa']);

    // Pelacakan Publik Customer & Ringkasan Dashboard
    Route::get('/orders/{order}/status', [OrderApiController::class, 'trackStatus']);
    Route::get('/dashboard/summary', [OrderApiController::class, 'dashboardSummary']);
});
