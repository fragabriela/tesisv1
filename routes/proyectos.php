<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProyectoController;

// Rutas para la gestión de proyectos
Route::middleware(['auth'])->prefix('proyectos')->name('proyectos.')->group(function () {
    // Listado principal de proyectos
    Route::get('/', [ProyectoController::class, 'index'])->name('index')->middleware('permission:ver proyectos');
    
    // Crear nuevo proyecto
    Route::get('/create', [ProyectoController::class, 'create'])->name('create')->middleware('permission:crear proyectos');
    Route::post('/', [ProyectoController::class, 'store'])->name('store')->middleware('permission:crear proyectos');
    
    // Verificar URL de GitHub
    Route::post('/check-github-url', [ProyectoController::class, 'checkGitHubUrl'])->name('check-github-url');
    
    // Configuración de GitHub
    Route::get('/{id}/github-config', [ProyectoController::class, 'showGitHubConfig'])->name('github-config')->middleware('permission:configurar proyectos');
    Route::post('/{id}/github-config', [ProyectoController::class, 'saveGitHubConfig'])->name('save-github-config')->middleware('permission:configurar proyectos');
    
    // Configuración del proyecto
    Route::get('/{id}/setup', [ProyectoController::class, 'showSetup'])->name('setup')->middleware('permission:configurar proyectos');
    Route::post('/{id}/clone', [ProyectoController::class, 'cloneAndDetect'])->name('clone')->middleware('permission:configurar proyectos');
    
    // Despliegue del proyecto
    Route::get('/{id}/deploy', [ProyectoController::class, 'showDeploy'])->name('deploy')->middleware('permission:desplegar proyectos');
    Route::post('/{id}/deploy', [ProyectoController::class, 'deploy'])->name('do-deploy')->middleware('permission:desplegar proyectos');
    
    // Gestión del proyecto
    Route::get('/{id}', [ProyectoController::class, 'show'])->name('show')->middleware('permission:ver proyectos');
    Route::get('/{id}/logs', [ProyectoController::class, 'logs'])->name('logs')->middleware('permission:ver proyectos');
    Route::post('/{id}/stop', [ProyectoController::class, 'stop'])->name('stop')->middleware('permission:gestionar proyectos');
    Route::post('/{id}/restart', [ProyectoController::class, 'restart'])->name('restart')->middleware('permission:gestionar proyectos');
    
    // Docker diagnóstico y guía de instalación
    Route::get('/docker-troubleshoot', [ProyectoController::class, 'dockerTroubleshoot'])->name('docker-troubleshoot')->middleware('permission:gestionar proyectos');
    Route::get('/docker-install', [ProyectoController::class, 'dockerInstallGuide'])->name('docker-install')->middleware('permission:gestionar proyectos');
    Route::get('/docker-auto-install', [ProyectoController::class, 'dockerAutoInstall'])->name('docker-auto-install')->middleware('permission:gestionar proyectos');
    
    // Monitoring
    Route::get('/monitor', [ProyectoController::class, 'monitor'])->name('monitor')->middleware('permission:monitorear proyectos');
    
    // Gestión de visibilidad
    Route::post('/{id}/toggle-visibility', [ProyectoController::class, 'toggleVisibility'])->name('toggle-visibility')->middleware('permission:configurar proyectos');
});

// Rutas para acceder a los proyectos desplegados
Route::middleware(['project.proxy'])->group(function () {
    Route::get('projects/{id}', [ProyectoController::class, 'proxy'])->name('proyectos.proxy')
         ->where('id', '[0-9]+')->middleware('permission:ver proyectos');
    
    Route::get('projects/{id}/{path}', [ProyectoController::class, 'proxy'])
         ->where('id', '[0-9]+')->where('path', '.*')->middleware('permission:ver proyectos');
});
