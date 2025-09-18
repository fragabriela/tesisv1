<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-role {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user role and permissions';

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
        
        $this->info("Usuario: {$user->name} ({$user->email})");
        $this->info("ID: {$user->id}");
        
        $roles = $user->roles->pluck('name')->toArray();
        if (empty($roles)) {
            $this->warn("Este usuario no tiene roles asignados.");
        } else {
            $this->info("Roles: " . implode(', ', $roles));
        }
        
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        if (empty($permissions)) {
            $this->warn("Este usuario no tiene permisos.");
        } else {
            $this->info("Permisos: " . implode(', ', $permissions));
        }
        
        // Check if user has alumno or tutor record
        if ($user->alumno) {
            $this->info("Tiene registro de alumno: {$user->alumno->nombre} {$user->alumno->apellido}");
        }
        
        if ($user->tutor) {
            $this->info("Tiene registro de tutor: {$user->tutor->nombre} {$user->tutor->apellido}");
        }
        
        if (!$user->alumno && !$user->tutor) {
            $this->warn("No tiene registros de alumno ni tutor asociados.");
        }
    }
}
