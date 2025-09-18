<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Alumno;

class AssignUserToAlumno extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-to-alumno {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a user to an existing alumno record or create one';

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
        
        if ($user->alumno) {
            $this->warn("El usuario ya tiene un alumno asociado: {$user->alumno->nombre} {$user->alumno->apellido}");
            return;
        }
        
        // Buscar un alumno sin usuario asignado o crear uno nuevo
        $alumno = Alumno::whereNull('user_id')->first();
        
        if (!$alumno) {
            // Crear un nuevo alumno
            $alumno = Alumno::create([
                'user_id' => $user->id,
                'nombre' => $user->name,
                'apellido' => 'Usuario',
                'email' => $user->email,
                'telefono' => '',
                'cedula' => 'AUTO' . $user->id,
                'matricula' => 'MAT' . $user->id,
                'fecha_nacimiento' => '2000-01-01',
                'direccion' => '',
                'id_carrera' => 1, // Asignar a la primera carrera disponible
                'estado' => 'activo'
            ]);
            
            $this->info("Nuevo alumno creado para {$user->name}");
        } else {
            // Asignar al alumno existente
            $alumno->user_id = $user->id;
            $alumno->save();
            
            $this->info("Usuario {$user->name} asignado al alumno existente: {$alumno->nombre} {$alumno->apellido}");
        }
        
        $this->info("Alumno ID: {$alumno->id}");
    }
}
