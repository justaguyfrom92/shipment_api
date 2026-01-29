<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ShipmentController;

Route::prefix('v1')->group(function ()
{
	Route::get('/shipments', [ShipmentController::class, 'index']);
	Route::get('/shipments/today', [ShipmentController::class, 'today']);
	Route::get('/shipments/{id}', [ShipmentController::class, 'show']);
	Route::post('/shipments', [ShipmentController::class, 'store']);
	Route::put('/shipments/{id}', [ShipmentController::class, 'update']);
});
