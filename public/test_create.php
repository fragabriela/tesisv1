<?php
require '../vendor/autoload.php';

/**
 * Test script for creating new records
 * 
 * This script tests creating new records for both Alumno and Tutor models
 * directly and through the DB facade to diagnose creation issues.
 */

// Initialize Laravel app
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Tutor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

header('Content-Type: text/plain');
echo "===== TEST CREATE SCRIPT =====\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Test 1: Create a tutor using Eloquent
    echo "Test 1: Creating tutor using Eloquent model\n";
    $tutor = new Tutor();
    $tutor->nombre = "Test Tutor " . time();
    $tutor->apellido = "Apellido " . time();
    $tutor->email = "test_tutor_" . time() . "@test.com";
    $tutor->telefono = "123-" . rand(1000000, 9999999);
    $tutor->especialidad = "Test Especialidad";
    $tutor->biografia = "Test biografia creada con script de diagnóstico";
    $tutor->activo = true;
    
    $saved = $tutor->save();
    echo "Tutor saved (Eloquent): " . ($saved ? "YES" : "NO") . "\n";
    echo "Tutor ID: " . $tutor->id . "\n";
    
    // Verify the tutor was created correctly
    $tutor->refresh();
    echo "Tutor exists after refresh: " . ($tutor->id ? "YES" : "NO") . "\n";
    echo "Name after refresh: " . $tutor->nombre . "\n\n";
    
    // Test 2: Create a tutor using direct DB insertion
    echo "Test 2: Creating tutor using direct DB insertion\n";
    $tutorData = [
        'nombre' => "DB Tutor " . time(),
        'apellido' => "DB Apellido " . time(),
        'email' => "db_tutor_" . time() . "@test.com",
        'telefono' => "456-" . rand(1000000, 9999999),
        'especialidad' => "DB Test Especialidad",
        'biografia' => "Test biografia creada con inserción directa a DB",
        'activo' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];
    
    $tutorId = DB::table('tutores')->insertGetId($tutorData);
    echo "Tutor created with DB (ID): " . $tutorId . "\n";
    
    // Verify the tutor was created correctly
    $dbTutor = DB::table('tutores')->where('id', $tutorId)->first();
    echo "Tutor exists in DB: " . ($dbTutor ? "YES" : "NO") . "\n";
    echo "Name in DB: " . ($dbTutor ? $dbTutor->nombre : "NOT FOUND") . "\n\n";
    
    // Get a Carrera for Alumno tests
    $carrera = Carrera::first();
    if (!$carrera) {
        // Create a test carrera if none exists
        $carrera = new Carrera([
            'nombre' => 'Test Carrera ' . time(),
            'descripcion' => 'Carrera de prueba para test_create.php',
            'activo' => true
        ]);
        $carrera->save();
        echo "Created test Carrera with ID: " . $carrera->id . "\n";
    } else {
        echo "Using existing Carrera with ID: " . $carrera->id . "\n";
    }
    
    // Test 3: Create an alumno using Eloquent
    echo "\nTest 3: Creating alumno using Eloquent model\n";
    $alumno = new Alumno();
    $alumno->nombre = "Test Alumno " . time();
    $alumno->apellido = "Apellido " . time();
    $alumno->email = "test_alumno_" . time() . "@test.com";
    $alumno->telefono = "789-" . rand(1000000, 9999999);
    $alumno->cedula = "C" . time();
    $alumno->matricula = "M" . time();
    $alumno->fecha_nacimiento = "2000-01-01";
    $alumno->id_carrera = $carrera->id;
    $alumno->estado = "activo";
    
    // Check if direccion column exists
    if (Schema::hasColumn('alumnos', 'direccion')) {
        $alumno->direccion = "Test Dirección " . time();
        echo "Adding direccion field to alumno\n";
    }
    
    $saved = $alumno->save();
    echo "Alumno saved (Eloquent): " . ($saved ? "YES" : "NO") . "\n";
    echo "Alumno ID: " . $alumno->id . "\n";
    
    // Verify the alumno was created correctly
    $alumno->refresh();
    echo "Alumno exists after refresh: " . ($alumno->id ? "YES" : "NO") . "\n";
    echo "Name after refresh: " . $alumno->nombre . "\n";
    echo "Email after refresh: " . $alumno->email . "\n\n";
    
    // Test 4: Create an alumno using direct DB insertion
    echo "Test 4: Creating alumno using direct DB insertion\n";
    $alumnoData = [
        'nombre' => "DB Alumno " . time(),
        'apellido' => "DB Apellido " . time(),
        'email' => "db_alumno_" . time() . "@test.com",
        'telefono' => "012-" . rand(1000000, 9999999),
        'cedula' => "CD" . time(),
        'matricula' => "MD" . time(),
        'fecha_nacimiento' => "2000-01-01",
        'id_carrera' => $carrera->id,
        'estado' => "activo",
        'created_at' => now(),
        'updated_at' => now(),
    ];
    
    // Check if direccion column exists
    if (Schema::hasColumn('alumnos', 'direccion')) {
        $alumnoData['direccion'] = "DB Test Dirección " . time();
        echo "Adding direccion field to alumnoData\n";
    }
    
    $alumnoId = DB::table('alumnos')->insertGetId($alumnoData);
    echo "Alumno created with DB (ID): " . $alumnoId . "\n";
    
    // Verify the alumno was created correctly
    $dbAlumno = DB::table('alumnos')->where('id', $alumnoId)->first();
    echo "Alumno exists in DB: " . ($dbAlumno ? "YES" : "NO") . "\n";
    echo "Name in DB: " . ($dbAlumno ? $dbAlumno->nombre : "NOT FOUND") . "\n";
    echo "Email in DB: " . ($dbAlumno ? $dbAlumno->email : "NOT FOUND") . "\n\n";
    
    // Test 5: Try with DB transaction
    echo "Test 5: Creating records with explicit DB transactions\n";
    
    try {
        DB::beginTransaction();
        
        $transactionTutor = new Tutor();
        $transactionTutor->nombre = "Transaction Tutor " . time();
        $transactionTutor->apellido = "Transaction Apellido";
        $transactionTutor->email = "transaction_tutor_" . time() . "@test.com";
        $transactionTutor->telefono = "999-" . rand(1000000, 9999999);
        $transactionTutor->especialidad = "Transaction Especialidad";
        $transactionTutor->activo = true;
        $transactionTutor->save();
        
        $transactionAlumno = new Alumno();
        $transactionAlumno->nombre = "Transaction Alumno " . time();
        $transactionAlumno->apellido = "Transaction Apellido";
        $transactionAlumno->email = "transaction_alumno_" . time() . "@test.com";
        $transactionAlumno->telefono = "888-" . rand(1000000, 9999999);
        $transactionAlumno->cedula = "CT" . time();
        $transactionAlumno->matricula = "MT" . time();
        $transactionAlumno->fecha_nacimiento = "2000-01-01";
        $transactionAlumno->id_carrera = $carrera->id;
        $transactionAlumno->estado = "activo";
        $transactionAlumno->save();
        
        DB::commit();
        
        echo "Transaction committed successfully\n";
        echo "Transaction Tutor ID: " . $transactionTutor->id . "\n";
        echo "Transaction Alumno ID: " . $transactionAlumno->id . "\n\n";
    } catch (\Exception $e) {
        DB::rollBack();
        echo "Transaction failed: " . $e->getMessage() . "\n";
    }
    
    echo "===== ALL TESTS COMPLETED SUCCESSFULLY =====\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
