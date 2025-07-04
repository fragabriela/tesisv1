<?php
// Test script to directly update a tutor record

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tutor;
use Illuminate\Support\Facades\DB;

// Function to get a tutor by ID or the first one if ID is not provided
function getTutor($id = null) {
    if ($id) {
        return Tutor::find($id);
    }
    return Tutor::first();
}

// Get the tutor
$id = $_GET['id'] ?? null;
$tutor = getTutor($id);

if (!$tutor) {
    die("No tutor found" . ($id ? " with ID: $id" : ""));
}

echo "<h1>Testing Tutor Update</h1>";
echo "<h2>Original Tutor Data</h2>";
echo "<pre>";
print_r($tutor->toArray());
echo "</pre>";

// Test updating with Eloquent
try {
    echo "<h2>Attempting to update with Eloquent</h2>";
    
    // Make a change to the name
    $newName = $tutor->nombre . " (Updated " . date('Y-m-d H:i:s') . ")";
    $tutor->nombre = $newName;
    
    // Get the changed fields
    $changes = $tutor->getDirty();
    echo "<p>Fields to be updated: </p>";
    echo "<pre>";
    print_r($changes);
    echo "</pre>";
    
    // Save and check result
    $result = $tutor->save();
    echo "<p>Save result: " . ($result ? 'Success' : 'Failed') . "</p>";
    
    // Reload from database
    $updatedTutor = Tutor::find($tutor->id);
    echo "<h3>Tutor After Update</h3>";
    echo "<pre>";
    print_r($updatedTutor->toArray());
    echo "</pre>";
    
    // Check if the update was actually applied
    if ($updatedTutor->nombre === $newName) {
        echo "<p style='color:green; font-weight:bold;'>✅ Update was successful!</p>";
    } else {
        echo "<p style='color:red; font-weight:bold;'>❌ Update failed - name was not changed in the database.</p>";
    }
} catch (\Exception $e) {
    echo "<h3>Error updating tutor</h3>";
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
    $newDirectSqlName = $tutor->nombre . " (SQL Updated " . date('Y-m-d H:i:s') . ")";
    
    // Update using direct SQL
    $affected = DB::table('tutores')
        ->where('id', $tutor->id)
        ->update([
            'nombre' => $newDirectSqlName,
            'updated_at' => now()
        ]);
    
    echo "<p>SQL Update affected rows: $affected</p>";
    
    // Reload from database
    $updatedTutor = Tutor::find($tutor->id);
    echo "<h3>Tutor After SQL Update</h3>";
    echo "<pre>";
    print_r($updatedTutor->toArray());
    echo "</pre>";
    
    // Check if the update was actually applied
    if ($updatedTutor->nombre === $newDirectSqlName) {
        echo "<p style='color:green; font-weight:bold;'>✅ SQL Update was successful!</p>";
    } else {
        echo "<p style='color:red; font-weight:bold;'>❌ SQL Update failed - name was not changed in the database.</p>";
    }
} catch (\Exception $e) {
    echo "<h3>Error updating tutor with SQL</h3>";
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
    $columns = DB::select('SHOW COLUMNS FROM tutores');
    echo "<h3>Table Structure:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (\Exception $e) {
    echo "<p style='color:red'>Database connection error: " . $e->getMessage() . "</p>";
}
