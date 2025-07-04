<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alumno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorAlumnoUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:alumno-updates {--fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and validate alumno updates, optionally fixing issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Alumno Update Monitoring...');
        
        // Get a test alumno, or create one if needed
        $alumno = $this->getOrCreateTestAlumno();
        
        if (!$alumno) {
            $this->error('Failed to get or create a test alumno');
            return 1;
        }
        
        $this->info("Using test alumno: {$alumno->id} - {$alumno->nombre} {$alumno->apellido}");
        
        // Test update via Eloquent
        $result = $this->testEloquentUpdate($alumno);
        
        if ($result) {
            $this->info('✅ Eloquent update successful');
        } else {
            $this->error('❌ Eloquent update failed');
            
            if ($this->option('fix')) {
                $this->info('Attempting to fix update functionality...');
                $this->fixUpdateFunctionality();
            }
        }
        
        // Test update via direct SQL
        $result = $this->testSqlUpdate($alumno);
        
        if ($result) {
            $this->info('✅ SQL update successful');
        } else {
            $this->error('❌ SQL update failed');
            
            if ($this->option('fix') && !$this->testSqlUpdateWithTransaction($alumno)) {
                $this->error('Could not fix SQL update functionality');
            }
        }
        
        // Test other database functions
        $this->testOtherDatabaseFunctions();
        
        $this->info('Update monitoring complete');
        return 0;
    }
    
    /**
     * Get an existing alumno or create a new test one
     */
    protected function getOrCreateTestAlumno()
    {
        try {
            // Try to get an existing alumno
            $alumno = Alumno::first();
            
            // Create a test alumno if none exists
            if (!$alumno) {
                // Get a carrera first
                $carrera = \App\Models\Carrera::first();
                
                if (!$carrera) {
                    // Create a test carrera if none exists
                    $carrera = new \App\Models\Carrera([
                        'nombre' => 'Test Carrera ' . time(),
                        'descripcion' => 'Carrera de prueba para monitoreo',
                        'activo' => true
                    ]);
                    $carrera->save();
                }
                
                // Create the test alumno
                $alumno = new Alumno([
                    'nombre' => 'Test Monitor',
                    'apellido' => 'Apellido Test ' . time(),
                    'email' => 'test_monitor_' . time() . '@example.com',
                    'telefono' => '123-' . rand(100000, 999999),
                    'cedula' => 'TM' . time(),
                    'matricula' => 'M' . time(),
                    'fecha_nacimiento' => '2000-01-01',
                    'id_carrera' => $carrera->id,
                    'estado' => 'activo',
                ]);
                $alumno->save();
            }
            
            return $alumno;
        } catch (\Exception $e) {
            $this->error('Error getting/creating test alumno: ' . $e->getMessage());
            Log::error('MonitorAlumnoUpdates: Error getting/creating test alumno: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Test update via Eloquent
     */
    protected function testEloquentUpdate($alumno)
    {
        try {
            $originalName = $alumno->nombre;
            $newName = 'Test Update ' . time();
            
            // Update the alumno
            $alumno->nombre = $newName;
            $result = $alumno->save();
            
            // Verify update by refreshing from database
            $alumno->refresh();
            $this->info("Original name: {$originalName}, New name: {$alumno->nombre}");
            
            return $alumno->nombre === $newName && $result;
        } catch (\Exception $e) {
            $this->error('Error during Eloquent update test: ' . $e->getMessage());
            Log::error('MonitorAlumnoUpdates: Error during Eloquent update test: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test update via direct SQL
     */
    protected function testSqlUpdate($alumno)
    {
        try {
            $newName = 'SQL Update ' . time();
            
            // Update the alumno using direct SQL
            $affected = DB::table('alumnos')
                ->where('id', $alumno->id)
                ->update([
                    'nombre' => $newName,
                    'updated_at' => now()
                ]);
            
            // Verify update by retrieving from database
            $alumno->refresh();
            $this->info("Updated name via SQL: {$alumno->nombre}, Affected rows: {$affected}");
            
            return $alumno->nombre === $newName && $affected === 1;
        } catch (\Exception $e) {
            $this->error('Error during SQL update test: ' . $e->getMessage());
            Log::error('MonitorAlumnoUpdates: Error during SQL update test: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test update via direct SQL with transaction
     */
    protected function testSqlUpdateWithTransaction($alumno)
    {
        try {
            $newName = 'SQL Transaction Update ' . time();
            
            // Start a transaction
            DB::beginTransaction();
            
            // Update the alumno using direct SQL
            $affected = DB::table('alumnos')
                ->where('id', $alumno->id)
                ->update([
                    'nombre' => $newName,
                    'updated_at' => now()
                ]);
            
            // Commit the transaction
            DB::commit();
            
            // Verify update by retrieving from database
            $alumno->refresh();
            $this->info("Updated name via SQL transaction: {$alumno->nombre}, Affected rows: {$affected}");
            
            return $alumno->nombre === $newName && $affected === 1;
        } catch (\Exception $e) {
            // Roll back the transaction
            DB::rollBack();
            $this->error('Error during SQL transaction update test: ' . $e->getMessage());
            Log::error('MonitorAlumnoUpdates: Error during SQL transaction update test: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test other database functions
     */
    protected function testOtherDatabaseFunctions()
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            $this->info('✅ Database connection successful');
            
            // Test select query
            $count = DB::table('alumnos')->count();
            $this->info("✅ Database query successful - Total alumnos: {$count}");
            
            // Check for table columns
            $hasColumn = Schema::hasColumn('alumnos', 'direccion');
            $this->info("✅ Schema check successful - Has direccion column: " . ($hasColumn ? 'Yes' : 'No'));
            
        } catch (\Exception $e) {
            $this->error('Error testing database functions: ' . $e->getMessage());
            Log::error('MonitorAlumnoUpdates: Error testing database functions: ' . $e->getMessage());
        }
    }
    
    /**
     * Fix update functionality issues
     */
    protected function fixUpdateFunctionality()
    {
        try {
            $this->info('Checking database structure...');
            
            // Check if the alumnos table exists
            if (!Schema::hasTable('alumnos')) {
                $this->error('Alumnos table does not exist');
                return false;
            }
            
            // Try to optimize the database
            $this->info('Optimizing database tables...');
            DB::statement('OPTIMIZE TABLE alumnos');
            
            // Clear caches
            $this->info('Clearing Laravel caches...');
            $this->call('cache:clear');
            $this->call('config:clear');
            $this->call('route:clear');
            $this->call('view:clear');
            
            return true;
        } catch (\Exception $e) {
            $this->error('Error fixing update functionality: ' . $e->getMessage());
            Log::error('MonitorAlumnoUpdates: Error fixing update functionality: ' . $e->getMessage());
            return false;
        }
    }
}
