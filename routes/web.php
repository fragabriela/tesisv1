<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TesisController;
use App\Http\Controllers\MiFormularioController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\TutorController;
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

// Authentication Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Registration Routes
use App\Http\Controllers\Auth\RegisterController;
Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

// Welcome page
Route::get('/', function () {
    return view('welcome');
});

// Debug routes
Route::get('/fix-database', [App\Http\Controllers\DatabaseFixController::class, 'fixDatabaseStructure']);
Route::get('/database-diagnostic', function () {
    return view('debug.database-diagnostic');
});
Route::match(['post', 'put'], '/debug/log-form-data', [App\Http\Controllers\DebugController::class, 'logFormData']);

// Form submission monitor routes
Route::get('/debug/form-monitor', [App\Http\Controllers\DebugController::class, 'formSubmissionMonitor'])->name('debug.form.monitor');
Route::get('/debug/form-submissions', [App\Http\Controllers\DebugController::class, 'getFormSubmissions'])->name('debug.get.form.submissions');
Route::get('/debug/alumnos', [App\Http\Controllers\DebugController::class, 'getAlumnos'])->name('debug.get.alumnos');
Route::post('/debug/test-alumno-update', [App\Http\Controllers\DebugController::class, 'testAlumnoUpdate'])->name('debug.test.alumno.update');
Route::get('/debug/database-info', [App\Http\Controllers\DebugController::class, 'databaseInfo'])->name('debug.database.info');

Route::get('/debug/test-update/{id?}', function($id = null) {
    $alumno = null;
    $carreras = \App\Models\Carrera::all();
    
    if ($id) {
        $alumno = \App\Models\Alumno::find($id);
    }
    
    return view('debug.test-update-form', compact('alumno', 'carreras'));
});

Route::get('/debug/direct-update/{id}', function($id) {
    try {
        // Get the current alumno
        $alumno = \App\Models\Alumno::findOrFail($id);
        $oldName = $alumno->nombre;
        
        // Update using raw DB query to bypass any model issues
        $updated = \DB::table('alumnos')
            ->where('id', $id)
            ->update([
                'nombre' => $oldName . ' (updated via direct SQL)',
                'updated_at' => now()
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Direct update completed',
            'result' => $updated,
            'alumno' => \App\Models\Alumno::find($id)
        ]);
    } catch (\Exception $e) {
        \Log::error('Direct update failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/api/schema/alumnos', function () {
    $columns = \DB::select('SHOW COLUMNS FROM alumnos');
    return response()->json([
        'table' => 'alumnos',
        'columns' => $columns,
        'has_direccion' => \Illuminate\Support\Facades\Schema::hasColumn('alumnos', 'direccion')
    ]);
});

Route::post('/api/test-alumno-create', function (\Illuminate\Http\Request $request) {
    try {
        $alumno = new \App\Models\Alumno();
        $alumno->nombre = $request->nombre;
        $alumno->apellido = $request->apellido;
        $alumno->email = $request->email;
        $alumno->telefono = $request->telefono;
        $alumno->cedula = $request->cedula;
        $alumno->matricula = $request->matricula;
        $alumno->fecha_nacimiento = $request->fecha_nacimiento;
        $alumno->id_carrera = $request->id_carrera;
        $alumno->estado = $request->estado;
        
        if (\Illuminate\Support\Facades\Schema::hasColumn('alumnos', 'direccion') && $request->has('direccion')) {
            $alumno->direccion = $request->direccion;
        }
        
        $alumno->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Test alumno created successfully',
            'alumno' => $alumno
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error creating test alumno',
            'error' => $e->getMessage(),
            'trace' => $e->getTrace()
        ], 500);
    }
});

Route::get('/debug-carrera', function () {
    try {
        // Test database connection
        $connection = DB::connection()->getPdo();
        $database_name = DB::connection()->getDatabaseName();
        
        $data = [
            'connection' => 'Connected to database: ' . $database_name,
        ];
        
        // Test carrera creation
        $testCarrera = new App\Models\Carrera([
            'nombre' => 'Test Carrera ' . time(),
            'descripcion' => 'Prueba de creación desde debug route',
            'activo' => true,
        ]);
        $testCarrera->save();
        
        $data['carrera_created'] = 'Created test carrera with ID: ' . $testCarrera->id;
        $data['carrera_data'] = $testCarrera->toArray();
        
        // List all carreras
        $data['all_carreras'] = App\Models\Carrera::all()->toArray();
        
        return response()->json($data);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/debug-carrera-form', function () {
    return view('debug.carrera-form');
});

Route::get('/debug-alumno', function () {
    try {
        // Test database connection
        $connection = DB::connection()->getPdo();
        $database_name = DB::connection()->getDatabaseName();
        
        $data = [
            'connection' => 'Connected to database: ' . $database_name,
        ];
        
        // Get a random carrera for testing
        $carrera = App\Models\Carrera::first();
        
        if (!$carrera) {
            // Create a test carrera if none exists
            $carrera = new App\Models\Carrera([
                'nombre' => 'Test Carrera ' . time(),
                'descripcion' => 'Carrera de prueba',
                'activo' => true
            ]);
            $carrera->save();
        }
        
        // Test alumno creation
        $testAlumno = new App\Models\Alumno();
        $testAlumno->nombre = 'Nombre Test ' . time();
        $testAlumno->apellido = 'Apellido Test';
        $testAlumno->email = 'test' . time() . '@example.com';
        $testAlumno->telefono = '123456789';
        $testAlumno->cedula = 'C' . time();
        $testAlumno->matricula = 'M' . time();
        $testAlumno->fecha_nacimiento = '2000-01-01';
        $testAlumno->id_carrera = $carrera->id;
        $testAlumno->estado = 'activo';
        $testAlumno->save();
        
        $data['alumno_created'] = 'Created test alumno with ID: ' . $testAlumno->id;
        $data['alumno_data'] = $testAlumno->toArray();
        
        // List some alumnos
        $data['recent_alumnos'] = App\Models\Alumno::latest()->take(5)->get()->toArray();
        
        return response()->json($data);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/debug-alumno-form', function () {
    return view('debug.alumno-form');
});

Route::post('/debug-alumno-store', function (Illuminate\Http\Request $request) {
    try {
        $data = [
            'request_all' => $request->all(),
            'has_token' => $request->has('_token')
        ];
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'telefono' => 'required|string|max:20',
            'cedula' => 'required|string|max:20|unique:alumnos,cedula',
            'matricula' => 'required|string|max:20|unique:alumnos,matricula',
            'fecha_nacimiento' => 'required|date',
            'id_carrera' => 'required|exists:carreras,id',
            'estado' => 'required|in:activo,inactivo',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->toArray()
            ], 422);
        }
        
        // Create and save alumno
        $alumno = new App\Models\Alumno();
        $alumno->nombre = $request->nombre;
        $alumno->apellido = $request->apellido;
        $alumno->email = $request->email;
        $alumno->telefono = $request->telefono;
        $alumno->cedula = $request->cedula;
        $alumno->matricula = $request->matricula;
        $alumno->fecha_nacimiento = $request->fecha_nacimiento;
        $alumno->id_carrera = $request->id_carrera;
        $alumno->estado = $request->estado;
        if ($request->has('direccion')) {
            $alumno->direccion = $request->direccion;
        }
        $alumno->save();
        
        $data['success'] = true;
        $data['alumno'] = $alumno->toArray();
        
        return response()->json($data);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::post('/debug-carrera-store', function (Illuminate\Http\Request $request) {
    try {
        $data = [
            'request_all' => $request->all(),
            'has_token' => $request->has('_token'),
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'has_activo' => $request->has('activo'),
            'activo_value' => $request->activo
        ];
        
        $carrera = new App\Models\Carrera();
        $carrera->nombre = $request->nombre;
        $carrera->descripcion = $request->descripcion;
        $carrera->activo = $request->has('activo');
        $carrera->save();
        
        $data['success'] = true;
        $data['carrera'] = $carrera->toArray();
        
        return response()->json($data);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:ver dashboard');

// Legacy routes
Route::get('/enviar-formulario', [MiFormularioController::class, 'index']);
Route::post('/guardar-formulario', [MiFormularioController::class, 'guardar'])->name('formulario.guardar');

// Tesis Routes
Route::middleware(['auth'])->group(function () {
    Route::get('tesis', [TesisController::class, 'index'])->name('tesis.index')->middleware('permission:ver tesis');
    Route::get('tesis/create', [TesisController::class, 'create'])->name('tesis.create')->middleware('permission:crear tesis');
    Route::post('tesis', [TesisController::class, 'store'])->name('tesis.store')->middleware('permission:crear tesis');
    Route::get('tesis/{tesis}', [TesisController::class, 'show'])->name('tesis.show')->middleware('permission:ver tesis');
    Route::get('tesis/{tesis}/edit', [TesisController::class, 'edit'])->name('tesis.edit')->middleware('permission:editar tesis');
    Route::put('tesis/{tesis}', [TesisController::class, 'update'])->name('tesis.update')->middleware('permission:editar tesis');
    Route::delete('tesis/{tesis}', [TesisController::class, 'destroy'])->name('tesis.destroy')->middleware('permission:eliminar tesis');
    Route::get('tesis/export-pdf', [TesisController::class, 'exportPDF'])->name('tesis.export.pdf')->middleware('permission:exportar tesis');
    Route::get('tesis/export-excel', [TesisController::class, 'exportExcel'])->name('tesis.export.excel')->middleware('permission:exportar tesis');
});
// Carrera Routes
Route::middleware(['auth'])->group(function () {
    Route::get('carrera', [CarreraController::class, 'index'])->name('carrera.index')->middleware('permission:ver carreras');
    Route::get('carrera/create', [CarreraController::class, 'create'])->name('carrera.create')->middleware('permission:crear carreras');
    Route::post('carrera', [CarreraController::class, 'store'])->name('carrera.store')->middleware('permission:crear carreras');
    Route::get('carrera/export-pdf', [CarreraController::class, 'exportPDF'])->name('carrera.export.pdf')->middleware('permission:exportar carreras');
    Route::get('carrera/export-excel', [CarreraController::class, 'exportExcel'])->name('carrera.export.excel')->middleware('permission:exportar carreras');
    Route::get('carrera/{carrera}', [CarreraController::class, 'show'])->name('carrera.show')->middleware('permission:ver carreras');
    Route::get('carrera/{carrera}/edit', [CarreraController::class, 'edit'])->name('carrera.edit')->middleware('permission:editar carreras');
    Route::put('carrera/{carrera}', [CarreraController::class, 'update'])->name('carrera.update')->middleware('permission:editar carreras');
    Route::delete('carrera/{carrera}', [CarreraController::class, 'destroy'])->name('carrera.destroy')->middleware('permission:eliminar carreras');
});

// Alumno Routes
Route::middleware(['auth'])->group(function () {
    Route::get('alumno', [AlumnoController::class, 'index'])->name('alumno.index')->middleware('permission:ver alumnos');
    Route::get('alumno/create', [AlumnoController::class, 'create'])->name('alumno.create')->middleware('permission:crear alumnos');
    Route::post('alumno', [AlumnoController::class, 'store'])->name('alumno.store')->middleware('permission:crear alumnos');
    Route::post('alumno/guardar', [AlumnoController::class, 'store'])->name('alumno.guardar')->middleware('permission:crear alumnos');
    Route::get('alumno/export-pdf', [AlumnoController::class, 'exportPDF'])->name('alumno.export.pdf')->middleware('permission:exportar alumnos');
    Route::get('alumno/export-excel', [AlumnoController::class, 'exportExcel'])->name('alumno.export.excel')->middleware('permission:exportar alumnos');
    Route::get('alumno/{alumno}', [AlumnoController::class, 'show'])->name('alumno.show')->middleware('permission:ver alumnos');
    Route::get('alumno/{alumno}/edit', [AlumnoController::class, 'edit'])->name('alumno.edit')->middleware('permission:editar alumnos');
    Route::put('alumno/{alumno}', [AlumnoController::class, 'update'])->name('alumno.update')->middleware('permission:editar alumnos');
    Route::delete('alumno/{alumno}', [AlumnoController::class, 'destroy'])->name('alumno.destroy')->middleware('permission:eliminar alumnos');
    Route::get('alumno/delete/{id}', [AlumnoController::class, 'destroy'])->name('alumno.delete')->middleware('permission:eliminar alumnos');
});

// Tutor Routes
// Tutor Routes
Route::middleware(['auth'])->group(function () {
    Route::get('tutor', [TutorController::class, 'index'])->name('tutor.index')->middleware('permission:ver tutores');
    Route::get('tutor/create', [TutorController::class, 'create'])->name('tutor.create')->middleware('permission:crear tutores');
    Route::post('tutor', [TutorController::class, 'store'])->name('tutor.store')->middleware('permission:crear tutores');
    Route::get('tutor/{tutor}', [TutorController::class, 'show'])->name('tutor.show')->middleware('permission:ver tutores');
    Route::get('tutor/{tutor}/edit', [TutorController::class, 'edit'])->name('tutor.edit')->middleware('permission:editar tutores');
    Route::put('tutor/{tutor}', [TutorController::class, 'update'])->name('tutor.update')->middleware('permission:editar tutores');
    Route::delete('tutor/{tutor}', [TutorController::class, 'destroy'])->name('tutor.destroy')->middleware('permission:eliminar tutores');
    Route::get('tutor/export-pdf', [TutorController::class, 'exportPDF'])->name('tutor.export.pdf')->middleware('permission:exportar tutores');
    Route::get('tutor/export-excel', [TutorController::class, 'exportExcel'])->name('tutor.export.excel')->middleware('permission:exportar tutores');
});

// Rutas para la gestión de proyectos
Route::middleware(['auth'])->prefix('proyectos')->name('proyectos.')->group(function () {
    // Listado principal de proyectos
    Route::get('/', [App\Http\Controllers\ProyectoController::class, 'index'])->name('index')->middleware('permission:ver proyectos');
    
    // Crear un nuevo proyecto
    Route::get('/create', [App\Http\Controllers\ProyectoController::class, 'create'])->name('create')->middleware('permission:crear proyectos');
    Route::post('/', [App\Http\Controllers\ProyectoController::class, 'store'])->name('store')->middleware('permission:crear proyectos');
    
    // Configuración de GitHub
    Route::get('/{id}/github-config', [App\Http\Controllers\ProyectoController::class, 'showGitHubConfig'])->name('github-config')->middleware('permission:configurar proyectos');
    Route::post('/{id}/github-config', [App\Http\Controllers\ProyectoController::class, 'saveGitHubConfig'])->name('save-github-config')->middleware('permission:configurar proyectos');
    
    // Configuración del proyecto
    Route::get('/{id}/setup', [App\Http\Controllers\ProyectoController::class, 'showSetup'])->name('setup')->middleware('permission:configurar proyectos');
    Route::post('/{id}/clone', [App\Http\Controllers\ProyectoController::class, 'cloneAndDetect'])->name('clone')->middleware('permission:configurar proyectos');
    
    // Despliegue del proyecto
    Route::get('/{id}/deploy', [App\Http\Controllers\ProyectoController::class, 'showDeploy'])->name('deploy')->middleware('permission:desplegar proyectos');
    Route::post('/{id}/deploy', [App\Http\Controllers\ProyectoController::class, 'deploy'])->name('do-deploy')->middleware('permission:desplegar proyectos');
    
    // Gestión del proyecto
    Route::get('/{id}', [App\Http\Controllers\ProyectoController::class, 'show'])->name('show')->middleware('permission:ver proyectos');
    Route::get('/{id}/logs', [App\Http\Controllers\ProyectoController::class, 'logs'])->name('logs')->middleware('permission:ver proyectos');
    Route::post('/{id}/stop', [App\Http\Controllers\ProyectoController::class, 'stop'])->name('stop')->middleware('permission:gestionar proyectos');
    Route::post('/{id}/restart', [App\Http\Controllers\ProyectoController::class, 'restart'])->name('restart')->middleware('permission:gestionar proyectos');
    
    // Monitoring
    Route::get('/monitor', [App\Http\Controllers\ProyectoController::class, 'monitor'])->name('monitor')->middleware('permission:monitorear proyectos');
    
    // Diagnóstico de Docker
    Route::get('/docker-troubleshoot', [App\Http\Controllers\ProyectoController::class, 'dockerTroubleshoot'])->name('proyectos.docker-troubleshoot')->middleware('permission:configurar proyectos');
    Route::get('/docker-install-guide', [App\Http\Controllers\ProyectoController::class, 'dockerInstallGuide'])->name('proyectos.docker-install')->middleware('permission:configurar proyectos');
    Route::get('/docker-auto-install', [App\Http\Controllers\ProyectoController::class, 'dockerAutoInstall'])->name('proyectos.docker-auto-install')->middleware('permission:configurar proyectos');
    
    // Gestión de visibilidad
    Route::post('/{id}/toggle-visibility', [App\Http\Controllers\ProyectoController::class, 'toggleVisibility'])->name('toggle-visibility')->middleware('permission:configurar proyectos');
    
    // Ruta para verificar URL de GitHub (debe ir antes de rutas con parámetros)
    Route::post('/check-github-url', [App\Http\Controllers\ProyectoController::class, 'checkGitHubUrl'])->name('check-github-url');
});

// Rutas para acceder a los proyectos desplegados
Route::middleware(['project.proxy'])->group(function () {
    Route::get('projects/{id}', function ($id) {
        // Esta ruta será interceptada por el middleware ProjectProxyMiddleware
    })->where('id', '[0-9]+')->middleware('permission:ver proyectos')->name('proyectos.proxy');
    
    Route::get('projects/{id}/{path}', function ($id, $path) {
        // Esta ruta será interceptada por el middleware ProjectProxyMiddleware
    })->where('id', '[0-9]+')->where('path', '.*')->middleware('permission:ver proyectos');
});



