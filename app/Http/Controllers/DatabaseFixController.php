<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseFixController extends Controller
{
    public function fixDatabaseStructure()
    {
        $output = [];
        
        // Check for direccion column in alumnos table
        $output[] = "Checking for direccion column in alumnos table...";
        
        try {
            if (!Schema::hasColumn('alumnos', 'direccion')) {
                $output[] = "Column 'direccion' does not exist. Adding it now...";
                
                Schema::table('alumnos', function ($table) {
                    $table->string('direccion')->nullable()->after('fecha_nacimiento');
                });
                
                $output[] = "Column 'direccion' added successfully.";
                Log::info("Column 'direccion' added to alumnos table via web fix.");
            } else {
                $output[] = "Column 'direccion' already exists. No action needed.";
            }
        } catch (\Exception $e) {
            $output[] = "Error: " . $e->getMessage();
            Log::error("Failed to add 'direccion' column: " . $e->getMessage());
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Database structure check completed',
            'output' => $output
        ]);
    }
}
