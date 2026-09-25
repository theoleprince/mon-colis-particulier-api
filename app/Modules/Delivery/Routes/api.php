<?php

use App\Modules\Delivery\Http\Controllers\ConfigurationController;
use App\Modules\Delivery\Http\Controllers\DeliveryController;
use App\Modules\Delivery\Http\Controllers\EstimateController;
use Illuminate\Support\Facades\Route;

// Reference data: public (shown before sign-in).
Route::get('/configurations/nature-colis', [ConfigurationController::class, 'natures']);
Route::get('/configurations/expedition', [ConfigurationController::class, 'expedition']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tarifs/estimer', EstimateController::class);

    Route::post('/livraisons', [DeliveryController::class, 'store']);
    // Path proposed to the app in docs/DEMANDES-API (2026-09-09).
    Route::post('/livraisons/create', [DeliveryController::class, 'store']);
    Route::get('/livraisons/mes-livraisons', [DeliveryController::class, 'index']);
    Route::get('/livraisons/{reference}', [DeliveryController::class, 'show']);
    Route::post('/livraisons/{reference}/medias', [DeliveryController::class, 'uploadMedia']);
    Route::post('/livraisons/{reference}/annuler', [DeliveryController::class, 'cancel']);
});
