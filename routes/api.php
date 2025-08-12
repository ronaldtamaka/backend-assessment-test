<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Api\DebitCardController;
use App\Http\Controllers\Api\DebitCardTransactionController;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('debit-cards', DebitCardController::class);
    Route::get('debit-card-transactions', [DebitCardTransactionController::class, 'index']);
    Route::post('debit-card-transactions', [DebitCardTransactionController::class, 'store']);
    Route::get('debit-card-transactions/{debitCardTransaction}', [DebitCardTransactionController::class, 'show']);
});
