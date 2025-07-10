<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixEstadoValuesSeeder extends Seeder
{
    /**
     * Fix any inconsistencies in the estado field of the tesis table.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Fixing tesis estado values...');

        if (!Schema::hasTable('tesis')) {
            $this->command->error('The tesis table does not exist!');
            return;
        }

        // Fix "en progreso" (with space) to "en_progreso" (with underscore)
        $updatedCount = DB::table('tesis')
            ->where('estado', 'en progreso')
            ->update(['estado' => 'en_progreso']);
        
        $this->command->info("Fixed {$updatedCount} tesis records with 'en progreso' to 'en_progreso'");

        // Fix "finalizado" to "completado"
        $updatedCount = DB::table('tesis')
            ->where('estado', 'finalizado')
            ->update(['estado' => 'completado']);
        
        $this->command->info("Fixed {$updatedCount} tesis records with 'finalizado' to 'completado'");

        // Other possible inconsistencies
        $invalidRecords = DB::table('tesis')
            ->whereNotIn('estado', ['pendiente', 'en_progreso', 'completado', 'rechazado'])
            ->count();
            
        if ($invalidRecords > 0) {
            $this->command->warn("Found {$invalidRecords} tesis records with invalid estado values!");
            $this->command->warn("You may need to fix these manually or extend this seeder.");
        } else {
            $this->command->info("All tesis estado values are now consistent with the database schema.");
        }
    }
}
