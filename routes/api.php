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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    // Rutas de API para tesis y proyectos
    Route::prefix('tesis')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\TesisApiController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\TesisApiController::class, 'show']);
        Route::post('/{id}/project', [\App\Http\Controllers\Api\TesisApiController::class, 'manageProject']);
    });
});
