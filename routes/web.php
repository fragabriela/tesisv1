<?php

use App\Http\Controllers\AlumnoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TesisController;
use App\Http\Controllers\MiFormularioController;
use App\Http\Controllers\CarreraController;
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
Route::get('carrera',[CarreraController::class,'index']);
Route::post('/guardar-carrera', [CarreraController::class, 'guardar'])->name('carrera.guardar');
Route::get('alumno',[AlumnoController::class,'index']); 
Route::post('/guardar-alumno', [AlumnoController::class, 'guardar'])->name('alumno.guardar');
Route::get('carrera/delete/{id}',[CarreraController::class,'delete'])->name('carrera.delete');
Route::get('carrera/update/{id}',[CarreraController::class,'update'])->name('carrera.update');
Route::post('/editar-carrera', [CarreraController::class, 'editar'])->name('carrera.editar');
    
