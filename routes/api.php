<?php

use App\Http\Controllers\Api\CurrencyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Currency API endpoints for external integrations
|
*/

Route::prefix('v1')->group(function () {
    // Currency rates
    Route::get('/rates', [CurrencyController::class, 'rates']);
    Route::get('/rates/{currency}', [CurrencyController::class, 'rate']);

    // Conversion
    Route::get('/convert', [CurrencyController::class, 'convert']);
    Route::post('/convert', [CurrencyController::class, 'convert']);

    // Historical data
    Route::get('/history/{currency}', [CurrencyController::class, 'history']);

    // Bank rates
    Route::get('/banks', [CurrencyController::class, 'banks']);
    Route::get('/banks/{currency}', [CurrencyController::class, 'banks']);
});

