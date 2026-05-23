<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\PriceAlertController;
use App\Http\Controllers\Api\SavingsPlanController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\WatchlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes — no auth required
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Live asset prices (cached 5s, public — used by unauthenticated live-event viewers)
    Route::get('/assets', [AssetController::class, 'index']);
    Route::get('/assets/{symbol}', [AssetController::class, 'show']);
    Route::get('/assets/{symbol}/candles', [AssetController::class, 'candles']);

    // ElasticSearch full-text asset search (public — used by search bars)
    Route::get('/search/assets', [SearchController::class, 'assets']);

    /*
    |--------------------------------------------------------------------------
    | Authenticated routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // Orders
        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'destroy']);

        // Savings plans
        Route::apiResource('savings-plans', SavingsPlanController::class)
             ->only(['index', 'store', 'show', 'destroy']);
        Route::get('savings-plans/{savingsPlan}/projection', [SavingsPlanController::class, 'projection']);

        // Portfolio
        Route::get('/portfolio', [PortfolioController::class, 'show']);
        Route::post('/portfolio/refresh', [PortfolioController::class, 'refresh']);

        // Price alerts
        Route::get('/price-alerts', [PriceAlertController::class, 'index']);
        Route::post('/price-alerts', [PriceAlertController::class, 'store']);
        Route::delete('/price-alerts/{priceAlert}', [PriceAlertController::class, 'destroy']);

        // Watchlist
        Route::get('/watchlist', [WatchlistController::class, 'index']);
        Route::post('/watchlist', [WatchlistController::class, 'store']);
        Route::delete('/watchlist/{asset}', [WatchlistController::class, 'destroy']);

    });
});
