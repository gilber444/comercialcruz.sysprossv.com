<?php

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

// Subida por API de los DTE PROCESADOS desde las cajas (token X-Sync-Token)
Route::post('sync/dtes-procesados/faltantes', [\App\Http\Controllers\Api\SyncDtesProcesadosController::class, 'faltantes'])
    ->middleware('throttle:600,1')->name('api.sync.dtes.faltantes');
Route::post('sync/dtes-procesados', [\App\Http\Controllers\Api\SyncDtesProcesadosController::class, 'recibir'])
    ->middleware('throttle:600,1')->name('api.sync.dtes.recibir');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->only([
        'id', 'name', 'user', 'email', 'phone', 'profile', 'status', 'image', 'empresa', 'sucursal',
    ]);
});
