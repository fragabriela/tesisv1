<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tutor;

class AssignUserToTutor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-to-tutor {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a user to an existing tutor record or create one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuario con email '{$email}' no encontrado.");
            return;
        }
        
        if ($user->tutor) {
            $this->warn("El usuario ya tiene un tutor asociado: {$user->tutor->nombre} {$user->tutor->apellido}");
            return;
        }
        
        // Buscar un tutor sin usuario asignado o crear uno nuevo
        $tutor = Tutor::whereNull('user_id')->first();
        
        if (!$tutor) {
            // Crear un nuevo tutor
            $tutor = Tutor::create([
                'user_id' => $user->id,
                'nombre' => $user->name,
                'apellido' => 'Usuario',
                'email' => $user->email,
                'telefono' => '',
                'especialidad' => 'General',
                'biografia' => 'Tutor creado automáticamente',
                'activo' => true
            ]);
            
            $this->info("Nuevo tutor creado para {$user->name}");
        } else {
            // Asignar al tutor existente
            $tutor->user_id = $user->id;
            $tutor->save();
            
            $this->info("Usuario {$user->name} asignado al tutor existente: {$tutor->nombre} {$tutor->apellido}");
        }
        
        $this->info("Tutor ID: {$tutor->id}");
    }
}
