<?php
// Test script to directly update a student record

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;

// Function to get a student by ID or the first one if ID is not provided
function getStudent($id = null) {
    if ($id) {
        return Alumno::find($id);
    }
    return Alumno::first();
}

// Get the student
$id = $_GET['id'] ?? null;
$student = getStudent($id);

if (!$student) {
    die("No student found" . ($id ? " with ID: $id" : ""));
}

echo "<h1>Testing Student Update</h1>";
echo "<h2>Original Student Data</h2>";
echo "<pre>";
print_r($student->toArray());
echo "</pre>";

// Test updating with Eloquent
try {
    echo "<h2>Attempting to update with Eloquent</h2>";
    
    // Make a change to the name
    $newName = $student->nombre . " (Updated " . date('Y-m-d H:i:s') . ")";
    $student->nombre = $newName;
    
    // Get the changed fields
    $changes = $student->getDirty();
    echo "<p>Fields to be updated: </p>";
    echo "<pre>";
    print_r($changes);
    echo "</pre>";
    
    // Save and check result
    $result = $student->save();
    echo "<p>Save result: " . ($result ? 'Success' : 'Failed') . "</p>";
    
    // Reload from database
    $updatedStudent = Alumno::find($student->id);
    echo "<h3>Student After Update</h3>";
    echo "<pre>";
    print_r($updatedStudent->toArray());
    echo "</pre>";
    
    // Check if the update was actually applied
    if ($updatedStudent->nombre === $newName) {
        echo "<p style='color:green; font-weight:bold;'>✅ Update was successful!</p>";
    } else {
        echo "<p style='color:red; font-weight:bold;'>❌ Update failed - name was not changed in the database.</p>";
    }
} catch (\Exception $e) {
    echo "<h3>Error updating student</h3>";
    echo "<pre style='color:red'>";
    print_r($e->getMessage());
    echo "\n\nStack trace:\n";
    print_r($e->getTraceAsString());
    echo "</pre>";
}

// Test updating with raw SQL
try {
    echo "<h2>Attempting to update with direct SQL</h2>";
    
    // Make a change to the name
    $newDirectSqlName = $student->nombre . " (SQL Updated " . date('Y-m-d H:i:s') . ")";
    
    // Update using direct SQL
    $affected = DB::table('alumnos')
        ->where('id', $student->id)
        ->update([
            'nombre' => $newDirectSqlName,
            'updated_at' => now()
        ]);
    
    echo "<p>SQL Update affected rows: $affected</p>";
    
    // Reload from database
    $updatedStudent = Alumno::find($student->id);
    echo "<h3>Student After SQL Update</h3>";
    echo "<pre>";
    print_r($updatedStudent->toArray());
    echo "</pre>";
    
    // Check if the update was actually applied
    if ($updatedStudent->nombre === $newDirectSqlName) {
        echo "<p style='color:green; font-weight:bold;'>✅ SQL Update was successful!</p>";
    } else {
        echo "<p style='color:red; font-weight:bold;'>❌ SQL Update failed - name was not changed in the database.</p>";
    }
} catch (\Exception $e) {
    echo "<h3>Error updating student with SQL</h3>";
    echo "<pre style='color:red'>";
    print_r($e->getMessage());
    echo "\n\nStack trace:\n";
    print_r($e->getTraceAsString());
    echo "</pre>";
}

// Add database information
echo "<h2>Database Information</h2>";
try {
    $connection = DB::connection()->getPdo();
    echo "<p>Connected to database: " . DB::connection()->getDatabaseName() . "</p>";
    
    // Check table structure
    $columns = DB::select('SHOW COLUMNS FROM alumnos');
    echo "<h3>Table Structure:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (\Exception $e) {
    echo "<p style='color:red'>Database connection error: " . $e->getMessage() . "</p>";
}
