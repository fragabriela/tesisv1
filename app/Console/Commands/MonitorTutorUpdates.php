<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tutor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MonitorTutorUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:tutor-updates {--fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and validate tutor updates, optionally fixing issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Tutor Update Monitoring...');
        
        // Get a test tutor, or create one if needed
        $tutor = $this->getOrCreateTestTutor();
        
        if (!$tutor) {
            $this->error('Failed to get or create a test tutor');
            return 1;
        }
        
        $this->info("Using test tutor: {$tutor->id} - {$tutor->nombre} {$tutor->apellido}");
        
        // Test update via Eloquent
        $result = $this->testEloquentUpdate($tutor);
        
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
        $result = $this->testSqlUpdate($tutor);
        
        if ($result) {
            $this->info('✅ SQL update successful');
        } else {
            $this->error('❌ SQL update failed');
            
            if ($this->option('fix') && !$this->testSqlUpdateWithTransaction($tutor)) {
                $this->error('Could not fix SQL update functionality');
            }
        }
        
        // Test other database functions
        $this->testOtherDatabaseFunctions();
        
        $this->info('Update monitoring complete');
        return 0;
    }
    
    /**
     * Get an existing tutor or create a new test one
     */
    protected function getOrCreateTestTutor()
    {
        try {
            // Try to get an existing tutor
            $tutor = Tutor::first();
            
            // Create a test tutor if none exists
            if (!$tutor) {
                // Create the test tutor
                $tutor = new Tutor([
                    'nombre' => 'Test Monitor',
                    'apellido' => 'Apellido Test ' . time(),
                    'email' => 'test_monitor_' . time() . '@example.com',
                    'telefono' => '123-' . rand(100000, 999999),
                    'especialidad' => 'Testing',
                    'biografia' => 'Tutor creado para pruebas de monitoreo',
                    'activo' => true,
                ]);
                $tutor->save();
            }
            
            return $tutor;
        } catch (\Exception $e) {
            $this->error('Error getting/creating test tutor: ' . $e->getMessage());
            Log::error('MonitorTutorUpdates: Error getting/creating test tutor: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Test update via Eloquent
     */
    protected function testEloquentUpdate($tutor)
    {
        try {
            $originalName = $tutor->nombre;
            $newName = 'Test Update ' . time();
            
            // Update the tutor
            $tutor->nombre = $newName;
            $result = $tutor->save();
            
            // Verify update by refreshing from database
            $tutor->refresh();
            $this->info("Original name: {$originalName}, New name: {$tutor->nombre}");
            
            return $tutor->nombre === $newName && $result;
        } catch (\Exception $e) {
            $this->error('Error during Eloquent update test: ' . $e->getMessage());
            Log::error('MonitorTutorUpdates: Error during Eloquent update test: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test update via direct SQL
     */
    protected function testSqlUpdate($tutor)
    {
        try {
            $newName = 'SQL Update ' . time();
            
            // Update the tutor using direct SQL
            $affected = DB::table('tutores')
                ->where('id', $tutor->id)
                ->update([
                    'nombre' => $newName,
                    'updated_at' => now()
                ]);
            
            // Verify update by retrieving from database
            $tutor->refresh();
            $this->info("Updated name via SQL: {$tutor->nombre}, Affected rows: {$affected}");
            
            return $tutor->nombre === $newName && $affected === 1;
        } catch (\Exception $e) {
            $this->error('Error during SQL update test: ' . $e->getMessage());
            Log::error('MonitorTutorUpdates: Error during SQL update test: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test update via direct SQL with transaction
     */
    protected function testSqlUpdateWithTransaction($tutor)
    {
        try {
            $newName = 'SQL Transaction Update ' . time();
            
            // Start a transaction
            DB::beginTransaction();
            
            // Update the tutor using direct SQL
            $affected = DB::table('tutores')
                ->where('id', $tutor->id)
                ->update([
                    'nombre' => $newName,
                    'updated_at' => now()
                ]);
            
            // Commit the transaction
            DB::commit();
            
            // Verify update by retrieving from database
            $tutor->refresh();
            $this->info("Updated name via SQL transaction: {$tutor->nombre}, Affected rows: {$affected}");
            
            return $tutor->nombre === $newName && $affected === 1;
        } catch (\Exception $e) {
            // Roll back the transaction
            DB::rollBack();
            $this->error('Error during SQL transaction update test: ' . $e->getMessage());
            Log::error('MonitorTutorUpdates: Error during SQL transaction update test: ' . $e->getMessage());
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
            $count = DB::table('tutores')->count();
            $this->info("✅ Database query successful - Total tutores: {$count}");
            
            // Check for table columns
            $hasColumn = Schema::hasColumn('tutores', 'biografia');
            $this->info("✅ Schema check successful - Has biografia column: " . ($hasColumn ? 'Yes' : 'No'));
            
        } catch (\Exception $e) {
            $this->error('Error testing database functions: ' . $e->getMessage());
            Log::error('MonitorTutorUpdates: Error testing database functions: ' . $e->getMessage());
        }
    }
    
    /**
     * Fix update functionality issues
     */
    protected function fixUpdateFunctionality()
    {
        try {
            $this->info('Checking database structure...');
            
            // Check if the tutores table exists
            if (!Schema::hasTable('tutores')) {
                $this->error('Tutores table does not exist');
                return false;
            }
            
            // Try to optimize the database
            $this->info('Optimizing database tables...');
            DB::statement('OPTIMIZE TABLE tutores');
            
            // Clear caches
            $this->info('Clearing Laravel caches...');
            $this->call('cache:clear');
            $this->call('config:clear');
            $this->call('route:clear');
            $this->call('view:clear');
            
            return true;
        } catch (\Exception $e) {
            $this->error('Error fixing update functionality: ' . $e->getMessage());
            Log::error('MonitorTutorUpdates: Error fixing update functionality: ' . $e->getMessage());
            return false;
        }
    }
}
