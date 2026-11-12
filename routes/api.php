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
// sync:dtes-estado: DTE ya resueltos en el VPS (para bajarlos a la caja)
Route::post('sync/dtes-resueltos', [\App\Http\Controllers\Api\SyncDtesProcesadosController::class, 'resueltos'])
    ->middleware('throttle:600,1')->name('api.sync.dtes.resueltos');
// Subida por API de ventas, cajas, detalles, tockens, dtes, resumen, firmadors y recepcion
Route::post('sync/tablas-ventas', [\App\Http\Controllers\Api\SyncTablasVentasController::class, 'recibir'])
    ->middleware('throttle:600,1')->name('api.sync.tablas.ventas');
// sync:inventario-api: existencia (inventarios) y kardex de la caja
Route::post('sync/inventario', [\App\Http\Controllers\Api\SyncInventarioController::class, 'recibir'])
    ->middleware('throttle:600,1')->name('api.sync.inventario');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->only([
        'id', 'name', 'user', 'email', 'phone', 'profile', 'status', 'image', 'empresa', 'sucursal',
    ]);
});
