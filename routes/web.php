<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TesisController;
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
Route::post('/enviar-formulario', [MiFormularioController::class, 'guardar'])->name('formulario.guardar');