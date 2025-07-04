<?php

use App\Http\Controllers\Api\BillController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/bills', [BillController::class, 'index']);
    Route::post('/bills', [BillController::class, 'store']);
    Route::post('/bills/{bill}/pay', [BillController::class, 'markAsPaid']);
    Route::delete('/bills/{bill}', [BillController::class, 'destroy']);
});
