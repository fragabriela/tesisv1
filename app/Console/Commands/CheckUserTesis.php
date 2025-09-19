<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tesis;
use Illuminate\Console\Command;

class CheckUserTesis extends Command
{
    protected $signature = 'user:check-tesis {email}';
    protected $description = 'Check tesis associated with a user';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->with(['alumno', 'tutor'])->first();
        
        if (!$user) {
            $this->error("Usuario con email '{$email}' no encontrado.");
            return;
        }

        $this->info("Usuario: {$user->name} ({$user->email})");
        
        if ($user->alumno) {
            $this->info("Alumno asociado: {$user->alumno->nombre} {$user->alumno->apellido} (ID: {$user->alumno->id})");
            
            $tesis = Tesis::where('alumno_id', $user->alumno->id)->get();
            $this->info("Tesis como alumno: {$tesis->count()}");
            
            foreach ($tesis as $t) {
                $this->line("  - {$t->titulo} (ID: {$t->id})");
            }
        } else {
            $this->warn("No tiene alumno asociado");
        }
        
        if ($user->tutor) {
            $this->info("Tutor asociado: {$user->tutor->nombre} {$user->tutor->apellido} (ID: {$user->tutor->id})");
            
            $tesis = Tesis::where('tutor_id', $user->tutor->id)->get();
            $this->info("Tesis como tutor: {$tesis->count()}");
            
            foreach ($tesis as $t) {
                $this->line("  - {$t->titulo} (ID: {$t->id})");
            }
        } else {
            $this->warn("No tiene tutor asociado");
        }
    }
}