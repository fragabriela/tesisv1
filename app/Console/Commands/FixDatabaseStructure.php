<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixDatabaseStructure extends Command
{
    protected $signature = 'app:fix-database-structure';
    protected $description = 'Fixes any missing columns in the database structure';

    public function handle()
    {
        $this->info('Checking database structure...');
        
        // Check for direccion column in alumnos table
        if (!Schema::hasColumn('alumnos', 'direccion')) {
            $this->info('Adding missing direccion column to alumnos table...');
            try {
                Schema::table('alumnos', function ($table) {
                    $table->string('direccion')->nullable()->after('fecha_nacimiento');
                });
                $this->info('Successfully added direccion column to alumnos table.');
            } catch (\Exception $e) {
                $this->error('Failed to add direccion column: ' . $e->getMessage());
            }
        } else {
            $this->info('direccion column already exists in alumnos table.');
        }
        
        $this->info('Database structure check complete.');
        return Command::SUCCESS;
    }
}
