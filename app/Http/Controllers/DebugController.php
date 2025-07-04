<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\Alumno;
use App\Models\Carrera;

class DebugController extends Controller
{
    // Store form submissions for monitoring
    protected $submissionsFile = 'debug/form_submissions.json';
    protected $maxSubmissions = 20;
    
    public function logFormData(Request $request)
    {
        Log::info('DEBUG - Form data received: ' . json_encode($request->all()));
        Log::info('DEBUG - Method: ' . $request->method());
        Log::info('DEBUG - URL: ' . $request->url());
        Log::info('DEBUG - Headers: ' . json_encode($request->headers->all()));
        
        // Store submission for monitoring
        $this->storeFormSubmission([
            'route' => $request->route()->getName() ?? $request->path(),
            'method' => $request->method(),
            'time' => now()->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'data' => $request->all(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Form data logged',
            'data' => $request->all(),
        ]);
    }
    
    public function formSubmissionMonitor()
    {
        return view('debug.form-submission-monitor');
    }
    
    public function getFormSubmissions()
    {
        $submissions = $this->loadFormSubmissions();
        
        return response()->json([
            'submissions' => $submissions,
        ]);
    }
    
    public function getAlumnos()
    {
        $alumnos = Alumno::select('id', 'nombre', 'apellido')->get();
        
        return response()->json([
            'alumnos' => $alumnos,
        ]);
    }
    
    public function testAlumnoUpdate(Request $request)
    {
        try {
            $request->validate([
                'alumno_id' => 'required|exists:alumnos,id',
                'field' => 'required|in:nombre,apellido,telefono',
                'value' => 'required|string|max:255',
            ]);
            
            $alumno = Alumno::findOrFail($request->alumno_id);
            $oldValue = $alumno->{$request->field};
            
            // Update using Eloquent
            $alumno->{$request->field} = $request->value;
            $alumno->save();
            
            // Also test direct SQL update
            $directSqlUpdate = DB::table('alumnos')
                ->where('id', $request->alumno_id)
                ->update([
                    $request->field => $request->value . ' (via SQL)',
                    'updated_at' => now(),
                ]);
            
            // Verify the update by reloading
            $updatedAlumno = Alumno::find($request->alumno_id);
            
            return response()->json([
                'success' => true,
                'message' => 'Alumno actualizado correctamente',
                'alumno_id' => $request->alumno_id,
                'field' => $request->field,
                'old_value' => $oldValue,
                'new_value' => $updatedAlumno->{$request->field},
                'eloquent_updated' => true,
                'sql_updated' => $directSqlUpdate == 1,
                'alumno' => $updatedAlumno,
            ]);
        } catch (\Exception $e) {
            Log::error('Debug test update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar alumno',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function databaseInfo()
    {
        try {
            $tables = [];
            $tableNames = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
            
            foreach ($tableNames as $tableName) {
                $columns = Schema::getColumnListing($tableName);
                $columnDetails = [];
                
                foreach ($columns as $column) {
                    $type = DB::getSchemaBuilder()->getColumnType($tableName, $column);
                    $columnDetails[$column] = [
                        'type' => $type,
                        'nullable' => DB::getSchemaBuilder()->getConnection()->getDoctrineColumn($tableName, $column)->getNotnull() ? 'NO' : 'YES'
                    ];
                }
                
                $tables[$tableName] = $columnDetails;
            }
            
            return response()->json([
                'success' => true,
                'database' => DB::connection()->getDatabaseName(),
                'tables' => $tables,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información de la base de datos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
      public function storeFormSubmission(array $submission)
    {
        $submissions = $this->loadFormSubmissions();
        
        // Add new submission to the beginning of the array
        array_unshift($submissions, $submission);
        
        // Keep only the most recent submissions
        $submissions = array_slice($submissions, 0, $this->maxSubmissions);
        
        // Make sure storage directory exists
        $directory = dirname($this->submissionsFile);
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }
        
        // Save the submissions
        Storage::put($this->submissionsFile, json_encode($submissions, JSON_PRETTY_PRINT));
    }
    
    protected function loadFormSubmissions()
    {
        if (!Storage::exists($this->submissionsFile)) {
            return [];
        }
        
        $content = Storage::get($this->submissionsFile);
        return json_decode($content, true) ?? [];
    }
}
