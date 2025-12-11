<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tutor;
use App\Models\Alumno;

class FixUserAssociations extends Command
{
    protected $signature = 'fix:user-associations';
    protected $description = 'Fix missing associations between users and tutores/alumnos based on email';

    public function handle()
    {
        $this->info('🔧 Arreglando asociaciones faltantes...');
        
        // Fix missing tutor associations
        $this->fixTutorAssociations();
        
        // Fix missing alumno associations  
        $this->fixAlumnoAssociations();
        
        $this->info('✅ Asociaciones arregladas exitosamente');
    }
    
    private function fixTutorAssociations()
    {
        $this->info('📚 Verificando tutores...');
        
        $tutoresSinUser = Tutor::whereNull('user_id')->get();
        $fixed = 0;
        
        foreach ($tutoresSinUser as $tutor) {
            $user = User::where('email', $tutor->email)->first();
            if ($user) {
                $tutor->user_id = $user->id;
                $tutor->save();
                $this->line("✓ Asociado tutor {$tutor->nombre} {$tutor->apellido} con usuario {$user->name}");
                $fixed++;
            }
        }
        
        $this->info("📊 Tutores arreglados: {$fixed}");
    }
    
    private function fixAlumnoAssociations()
    {
        $this->info('🎓 Verificando alumnos...');
        
        $alumnosSinUser = Alumno::whereNull('user_id')->get();
        $fixed = 0;
        
        foreach ($alumnosSinUser as $alumno) {
            $user = User::where('email', $alumno->email)->first();
            if ($user) {
                $alumno->user_id = $user->id;
                $alumno->save();
                $this->line("✓ Asociado alumno {$alumno->nombre} {$alumno->apellido} con usuario {$user->name}");
                $fixed++;
            }
        }
        
        $this->info("📊 Alumnos arreglados: {$fixed}");
    }
}