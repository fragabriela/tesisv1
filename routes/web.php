<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TesisController;
use App\Http\Controllers\MiFormularioController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
//php artisan make:controller TesisController
Route::get('admin/pages',[TesisController::class,'index']);
Route::get('admin/pagesv2',[TesisController::class,'indexv2']);
Route::get('/enviar-formulario', [MiFormularioController::class, 'index']);
Route::post('/guardar-formulario', [MiFormularioController::class, 'guardar'])->name('formulario.guardar');